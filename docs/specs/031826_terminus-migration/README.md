# Terminus Migration Design Document

**Date:** 2026-03-18
**Status:** Draft / Exploratory
**Author:** Duncan Schouten, with research assistance from Claude

## Table of Contents

- [1. Problem Statement](#1-problem-statement)
- [2. Current Plugin Architecture](#2-current-plugin-architecture)
- [3. Plugin Ecosystem Analysis](#3-plugin-ecosystem-analysis)
- [4. Telemetry Analysis](#4-telemetry-analysis)
- [5. Adapter Approaches Considered](#5-adapter-approaches-considered)
- [6. Recommendation](#6-recommendation)
- [7. Rename: terminus to pantheon](#7-rename-terminus-to-pantheon)
- [8. Client-Server Architecture](#8-client-server-architecture)
- [9. Deprecation Timeline](#9-deprecation-timeline)
- [10. Open Questions](#10-open-questions)

---

## 1. Problem Statement

Terminus is a PHP-based CLI client for the Pantheon API. As Pantheon transitions to support Next.js, the CLI requiring PHP creates a poor experience for JavaScript-native developers who don't otherwise have PHP on their systems.

The plan is to rewrite the core CLI in Go (distributable as an npm package). The challenge is an extensive collection of PHP plugins written for Terminus — some known, many unknown — that would break if the CLI moves to a different language.

This document explores whether a plugin adapter is needed to support legacy PHP plugins, or whether a simpler migration strategy is sufficient, backed by telemetry data from Pantheon's Snowflake data warehouse.

---

## 2. Current Plugin Architecture

### How Plugins Are Discovered

Terminus uses `PluginDiscovery` to scan `composer.lock` in the `terminus_dependencies_dir` (typically `~/.terminus/terminus-dependencies/`). It identifies packages with `type: "terminus-plugin"` in their `composer.json`. Each plugin is validated for version compatibility via `extra.terminus.compatible-version` using semver. Commands are discovered via `CommandFileDiscovery` (Consolidation library) looking for `*Command.php` and `*Hook.php` files based on PSR-4 autoload configuration.

Key source files:
- `src/Terminus.php` (lines 512-530) — plugin loading orchestration
- `src/Plugins/PluginDiscovery.php` — discovery logic
- `src/Plugins/PluginInfo.php` — plugin metadata and version checks
- `src/Plugins/ComposerDependencyValidator.php` — dependency validation

### How Plugins Integrate with Core

Plugins are deeply coupled to the PHP runtime and Terminus's internal object graph:

**Base class inheritance:** All plugin commands extend `TerminusCommand` or subclasses like `SiteCommand`. These provide access to logging, I/O, configuration, the DI container, and session management.

**Trait-based dependency injection:** Plugins implement interfaces like `SiteAwareInterface`, `RequestAwareInterface`, `SessionAwareInterface`, and `ConfigAwareInterface`, using corresponding traits. These are injected via League\Container inflectors at runtime.

**Annotation-driven command registration:** Commands use Consolidation\AnnotatedCommand annotations (`@command`, `@authorize`, `@option`, `@hook validate`, etc.) for registration and lifecycle hooks.

**Rich model interactions:** Plugins interact with 20+ model classes (`Site`, `Environment`, `Workflow`, `Backup`, `Domain`, etc.) and their corresponding collection classes. Common patterns include `$this->getSiteEnv()`, `$env->getBackups()->create()`, `$workflow->checkProgress()`, `$env->deploy()`.

**Lifecycle hooks:** Plugins can hook into `@hook pre-init`, `@hook validate`, and `@hook post-command-event` to modify command behavior.

### Plugin Structure Requirements

A valid Terminus plugin must provide:
1. `composer.json` with `type: "terminus-plugin"`, `extra.terminus.compatible-version`, and PSR-4 autoload configuration
2. Command classes extending `TerminusCommand` or subclasses, with `@command` annotations
3. Optional hook classes with `@hook` annotated methods
4. No direct `require` on Terminus packages (handled via inflector injection)

---

## 3. Plugin Ecosystem Analysis

### Known Plugin Repositories

The `terminus-plugin-project` GitHub organization contains 25 community plugins. We examined 16 of them in depth and categorized them into three tiers of coupling with Terminus core.

### Tier 1: Orchestrators (~40% of plugins)

**Examples:** backup-all, genie, cantilever, upstream-deployment

These iterate over `$this->sites()`, call methods on Site/Environment models (`getBackups()->create()`, `applyUpstreamUpdates()`, `deploy()`, `commitChanges()`, `changeConnectionMode()`), and use `$this->log()` for output. They are workflow scripts built on Terminus's model layer.

Heavy use of: `SiteAwareTrait`, `$this->getSiteEnv()`, `$env->getBackups()`, `$workflow->checkProgress()`.

**Example — backup-all plugin:** Iterates all sites, optionally filters by framework/owner/org, checks SFTP diffstat, auto-commits pending changes, then creates backups for each element of each environment. Uses `Sites::fetch()`, `Sites::filterByName()`, `Environment::diffstat()`, `Environment::commitChanges()`, `Backups::create()`.

**Example — genie plugin:** Iterates all sites and re-invokes Terminus itself via `passthru($_SERVER['argv'][0])` to run arbitrary commands against each site.

### Tier 2: External Tool Launchers (~30% of plugins)

**Examples:** filer, wraith, hotfix, code

These use Terminus models to extract connection details (SFTP info, git URLs, domain names) and then shell out to external tools via `exec()`, `passthru()`, or `proc_open()`.

**Example — filer plugin:** Calls `$env->sftpConnectionInfo()` to get SFTP connection details, then launches FileZilla, Cyberduck, Transmit, or WinSCP via platform-specific `exec()` commands.

**Example — hotfix plugin:** Extends `SingleBackupCommand`, uses `RequestAwareTrait`, fetches site/environment details, clones the git repo locally via `passthru("git clone ...")`, creates branches, tags, and pushes. Also creates backups, monitors workflows, and clears caches through the model layer.

### Tier 3: External Service Integrators (~20% of plugins)

**Examples:** s3-sync, mustafa (CDN)

These pull data from Terminus models and interact with external APIs. They bring their own PHP dependencies.

**Example — s3-sync plugin:** Uses `SiteAwareTrait` and `RequestAwareTrait`, implements `ContainerAwareInterface`. Fetches backups via `$env->getBackups()->getFinishedBackups()`, then uploads them to S3 using `aws/aws-sdk-php` with concurrent promise-based uploads via Guzzle.

### Universal Coupling Point

Every plugin examined depends on `SiteAwareTrait` and `$this->getSiteEnv()`. The most common model interactions across all plugins:
- `$site->getEnvironments()` / `$env->getBackups()` / `$env->getDomains()`
- `$workflow->checkProgress()` / `$env->deploy()` / `$env->commitChanges()`
- `$env->connectionInfo()` / `$env->sftpConnectionInfo()`
- `$this->session()->getUser()`
- `$this->log()->notice()` / `$this->confirm()`

---

## 4. Telemetry Analysis

Data sourced from Pantheon's Snowflake data warehouse, specifically `STAGE_SEGMENT.PANTHEONAPI_TERMINUS_PRODUCTION.TERMINUS_API_REQUEST` (427M raw event records) and verified against the 143 core commands extracted from `@command` annotations in the Terminus source code.

### Overall Terminus Usage (Last 6 Months)

- 7,506 unique users
- 76,317 sites
- 411M total API requests

### Top Core Commands (Last 6 Months)

The top commands by request volume: `domain:list` (22M+), `drush` (10M+), `remote:wp` (5.3M), `remote:drush` (3.3M), `env:list` (2.9M), `auth:login` (2.1M), `site:info` (2M), `env:info` (1.8M).

A significant portion of usage is automated (CI/CD pipelines, cron jobs).

### Plugin Usage Breakdown (Last 6 Months)

All plugin commands combined account for 447,890 requests — 0.11% of all Terminus traffic.

**Build Tools (Pantheon-maintained, being deprecated):** 315,463 requests from ~400 users. Top commands: `build:env:delete:pr` (85K), `build:env:create` (77K), `build:env:push` (60K), `build:comment:add:pr` (22K), `build:comment:add:commit` (16K), `project:clu` (14K), `build:env:delete:ci` (13K).

**Old Secrets Commands (now merged into core):** 68,465 requests from ~300 users. These are pre-merge command names (`secrets:set`, `secrets:list`, `secrets:show`, `secrets:delete`, `secret:list`, `secret:set`, `secret:delete`) still being called by users who haven't updated.

**Customer-Specific Custom Plugins:** 56,748 requests from ~20 users. Internal plugins built by specific companies. High request counts driven by automation from 1-2 users each.
- LocalIQ/Gannett: `wp:switch:auth0` (8.6K reqs, 3 users), `wp:delete-gannett-users` (8K reqs, 2 users), `localiq:wp:connect:auth0` (2.3K reqs, 2 users), `wp:connect:auth0` (1.7K reqs, 4 users), `wp:connect:okta` (1.4K reqs, 6 users)
- Unknown orgs: `env:deploy:release` (21K reqs, 2 users), `upstream:updates:apply:release` (10K reqs, 2 users), `fctg:env:prepare` (491 reqs, 1 user), `checkEnv` (445 reqs, 1 user), `ots-commits:list-json` (416 reqs, 1 user)

**Community/Third-Party Plugins:** 7,214 requests from ~90 users — 0.002% of all traffic.
- `logs:get` — 4,907 requests, 77 users (most popular community plugin by far)
- `dash` — 858 requests, 5 users
- `scheduledjobs:schedule:list` — 353 requests, 28 users
- `ldb` — 313 requests, 14 users
- `cex` — 255 requests, 5 users
- `pancakes` / `site:pancakes` — 322 requests combined, 9-17 users
- `scheduledjobs:schedule:create` — 206 requests, 24 users

### Zero Usage Plugins

From the 25 terminus-plugin-project repos examined, the following have no telemetry hits over 6 months: backup-all, site:filer (all variants), genie, wraith, site:sync-s3, site:update (upstream-deployment), hotfix:env:create / hotfix:env:deploy, and every other plugin in that org not listed above.

### Telemetry Caveats

The Segment data tracks API requests that Terminus makes to the Pantheon API. Not all plugin activity results in API calls. Tier 2 plugins (external tool launchers) extract connection info via core API calls, then shell out to external tools — the plugin's command name may not appear in telemetry because tracking fires on the API request, not the Terminus command invocation.

890 users ran `self:plugin:install` over 6 months (12% of the user base), indicating the behavior of installing plugins is established even if the specific plugin commands don't appear at high volume in the data.

---

## 5. Adapter Approaches Considered

### Approach A: Embedded PHP Runtime

The Go binary bundles a minimal PHP interpreter (statically-compiled binary or WASM-based like php-wasm). When a legacy plugin command is invoked, Go spawns a PHP subprocess, loading a compatibility shim that provides the base classes backed by IPC calls to the Go core for API access, auth, and output.

**Pros:** True zero-friction for plugin users. You control the PHP version. Clean migration story.

**Cons:** Binary size increases 15-30MB. Must maintain a compatibility shim replicating the entire model layer. Plugins with Composer dependencies (like s3-sync with aws-sdk-php) still need dependency resolution, which means embedding Composer. Cross-platform builds get more complex. Two runtimes to debug.

**Verdict:** Overengineered for the actual usage data.

### Approach B: System PHP Delegation

Go Terminus checks for PHP on the system. If found and a legacy plugin is invoked, it delegates to a PHP subprocess with a compatibility shim. If not found, it tells the user they need PHP for legacy plugins.

**Pros:** Simpler to build and maintain. Composer dependency resolution works naturally. Smaller binary.

**Cons:** Doesn't solve the "JS developer who doesn't have PHP" problem. PHP version mismatches. Reintroduces PHP dependency for plugin users.

**Verdict:** Defeats the purpose of the Go migration.

### Approach C: Auto-Provisioned PHP

Go Terminus auto-downloads a standalone PHP binary to `~/.terminus/php/` on first use of a legacy plugin. Similar to how nvm/volta manage Node versions.

**Pros:** Core binary stays small. No PHP prerequisite for users who only use native Go commands. Lazy provisioning. You control the PHP version.

**Cons:** Network access required on first plugin use. Composer dependency resolution still a challenge. Corporate firewalls may block the download. Maintaining a PHP distribution channel.

**Verdict:** Clever but still complex, and the usage data doesn't justify it.

### Approach D: Transparent Fallthrough (Variant B with Manifest)

Go handles all core commands natively. During plugin install, Go invokes PHP once to extract command metadata (name, args, options, description) and writes it to a manifest file. On execution, Go checks the manifest, and if the command is a legacy plugin, delegates to a PHP subprocess.

**Pros:** Commands work exactly as before with no prefix. User doesn't know which commands are Go-native vs PHP-legacy.

**Cons:** Same core engineering challenge as all approaches — the compatibility shim. Discovery requires running PHP at least at install time.

**Verdict:** The most user-friendly adapter approach, but the usage data doesn't justify the investment.

### Core Engineering Challenge (All Approaches)

Regardless of how PHP arrives on the machine, all adapter approaches require a compatibility shim — a PHP-side layer that provides `TerminusCommand`, `SiteCommand`, `SiteAwareTrait`, `RequestAwareTrait`, etc., backed by IPC to the Go core. This shim must faithfully replicate the DI container bindings, model objects, and annotation-based command discovery. This is the same amount of work in every approach and represents the majority of the engineering cost.

---

## 6. Recommendation

**Do not build a PHP plugin adapter.** The telemetry data does not justify the investment.

Instead, pursue a three-part strategy:

### 6a. Targeted Customer Outreach

Identify the ~20 customer-specific plugin users through Salesforce/account data (join `USER_ID` from telemetry to account records) and start migration conversations well before the Go release. These are high-value accounts with custom tooling — the risk is churn, not support tickets. Start now, not at launch.

### 6b. Claude Skill for Plugin Migration

Build a Claude skill that assists plugin authors in rewriting PHP plugins for the new Go plugin SDK. This is high leverage: it makes migration low-effort for customers and reduces the burden on the Pantheon team.

### 6c. Go Plugin SDK

Design a clean, well-documented plugin SDK for Go-native plugins. This is the investment that pays forward — it defines the next generation of the plugin ecosystem.

### 6d. Terminus Classic

Keep the current PHP binary available as a separate download during the deprecation period. Users who need legacy plugins can continue using the PHP binary without any adapter or interop layer. This is dramatically cheaper than building a bridge between two runtimes.

---

## 7. Rename: terminus to pantheon

The CLI will be renamed from `terminus` to `pantheon` (the binary/command becomes `pantheon` instead of `terminus`). This is a natural fit for the Go rewrite:

- Clearer for new users, especially JS-native developers
- The different binary name provides a natural coexistence mechanism — `terminus` (PHP) and `pantheon` (Go) can exist on the same system without conflict
- All commands, flags, and output formats remain identical — only the binary name changes

### Migration Strategy for the Rename

Ship the Go binary as `pantheon`. Also install a `terminus` shim (symlink or wrapper) that passes all arguments through to `pantheon` but prints a one-time deprecation notice:

```
Note: "terminus" has been renamed to "pantheon".
This alias will be removed in a future release.
Please update your scripts to use "pantheon" instead.
```

This ensures:
- New users learn `pantheon` from the start
- Existing scripts continue to work with zero changes
- The deprecation message nudges updates at the user's own pace
- Telemetry can track whether invocations come through `terminus` (shim) or `pantheon` (direct) to measure migration progress

### Rationale for Not Making Other Breaking Changes

A major version transition is tempting as an opportunity to restructure the command tree or rename flags. This is inadvisable because:

- 411M requests over 6 months, with a large portion automated (CI/CD pipelines, cron jobs, GitHub Actions workflows). Every pipeline has hardcoded command names and parses output in specific formats.
- If `pantheon env:deploy` works exactly like `terminus env:deploy`, migration is a one-line find-and-replace. If commands are also restructured, every customer must audit and rewrite their automation.
- Years of blog posts, Stack Overflow answers, Pantheon docs, training materials, and agency runbooks reference current command names. Command compatibility preserves the value of that documentation.
- The exception: if specific commands have genuinely wrong, confusing, or insecure behavior, a major version transition is the natural time to fix those. But that's fixing bugs, not redesigning the interface.

---

## 8. Client-Server Architecture

An alternative architectural direction has been proposed: separating Terminus into a client-server model where all API processing moves to a server component and the CLI becomes a thin client that streams and displays output.

### The Concept

Instead of a monolithic CLI binary that handles everything — argument parsing, API authentication, business logic, output formatting — the system would be split into two components:

**Server (Go):** Handles all business logic — authenticating with the Pantheon API, making requests, orchestrating workflows, resolving site/environment objects, managing sessions. Exposes a well-defined protocol (e.g., local HTTP, Unix socket, or stdio-based JSON-RPC) for receiving commands and streaming responses.

**Client (thin):** Accepts user input, sends structured command requests to the server, and streams the text output to the terminal as it arrives. Because the client is trivially thin, it could be implemented in any language — Go, Node, even a shell script.

### How This Changes the Plugin Story

A client-server separation fundamentally reframes the plugin problem:

**Language-agnostic plugins.** If the server exposes a protocol for registering and dispatching commands, plugins don't need to be compiled into the same binary or written in the same language. A plugin could be a separate process — in PHP, Python, Node, Go, or anything — that registers itself with the server and handles specific command namespaces. The plugin communicates via the same protocol the client uses.

**No adapter needed.** Instead of embedding PHP in Go or bridging two runtimes, a legacy PHP plugin could run as its own process. It registers with the server on startup ("I handle `backup-all:*` commands"), and when a user runs `pantheon backup-all:create`, the server delegates to the PHP plugin process. The plugin makes API calls through the server (which handles auth and rate limiting), and streams output back through the server to the client.

**Multiple client surfaces.** The same server could serve a CLI client, a VS Code extension, a web-based dashboard, or a CI runner. This is particularly relevant for the Next.js audience — a Node-based client could be distributed as an npm package while the Go server handles the heavy lifting.

**Persistent or on-demand.** The server could run as a daemon (fast startup for subsequent commands, shared session state) or be spawned on demand per command invocation (simpler, no background process to manage). A daemon model would also enable features like watching workflows or streaming logs without repeated auth handshakes.

### Implications for the Migration

If this architecture is adopted, the plugin migration story changes significantly:

**For Pantheon-maintained plugins (Build Tools, Secrets, etc.):** These would be rewritten as native server-side handlers in Go. No change from the current recommendation.

**For customer-specific plugins:** Instead of rewriting in Go, customers could keep their PHP plugin code and wrap it as a plugin server process. The plugin would need to speak the new protocol instead of extending `TerminusCommand`, but the core logic — API calls, shell-outs, external service integration — could remain largely unchanged. The Claude skill could assist with this wrapper generation.

**For community plugins:** Same as customer plugins. The barrier to entry for a new plugin drops dramatically — no need to learn Go, no need to compile against a specific SDK version. Write a script in any language that speaks the protocol.

### Protocol Design Considerations

The protocol between server and clients/plugins would need to support:

- **Command registration:** Plugins declare what commands they handle, with metadata (name, arguments, options, description, authorization requirements).
- **Command dispatch:** Server routes incoming commands to the appropriate handler (built-in or plugin).
- **API proxy:** Plugins make Pantheon API calls through the server, which handles authentication, session management, and rate limiting. This replaces the current `RequestAwareTrait` / `SiteAwareTrait` pattern.
- **Streaming output:** Handlers stream text output back to the client in real time. This replaces `$this->log()->notice()`.
- **Interactive prompts:** Handlers can request user input (confirmations, passwords). This replaces `$this->confirm()` and `$this->io()->ask()`.
- **Progress reporting:** Long-running operations (backups, deployments) report progress. This replaces `$workflow->checkProgress()` loops.

Existing protocols to consider as models or direct implementations: JSON-RPC over stdio (used by LSP and MCP), gRPC (native Go support, strong typing), or a simple newline-delimited JSON over local HTTP.

### Relationship to Other Recommendations

The client-server architecture is complementary to the other recommendations in this document, not a replacement. The deprecation timeline, rename strategy, and customer outreach plan remain the same regardless. The architecture primarily affects:

- **Section 6c (Go Plugin SDK):** The SDK becomes a protocol specification rather than a Go library. Plugins in any language can implement it.
- **Section 6b (Claude Skill):** The skill would help wrap existing PHP plugins as protocol-speaking processes rather than rewriting them entirely in Go.
- **Section 9, Open Question 1:** The "what should the plugin interface look like" question is answered — it's a protocol, not a compiled interface.

### Trade-offs

**Pros:**
- Cleanest solution to the language-agnostic plugin problem
- Enables multiple client surfaces (CLI, IDE, web)
- Plugins become independently deployable and testable
- Server can enforce auth, rate limiting, and audit logging centrally
- Natural fit for streaming output from long-running operations

**Cons:**
- More complex to build than a monolithic CLI
- Latency overhead for local IPC (though minimal for a CLI tool)
- Debugging spans two processes instead of one
- Plugin lifecycle management (starting, stopping, health-checking plugin processes) adds operational complexity
- Protocol versioning and backward compatibility becomes a long-term concern

---

## 9. Deprecation Timeline

### Recommended: Option C — 12-Month Deprecation with Milestones

**Month 0 — Go Launch**
- Go binary ships as `pantheon`
- `terminus` shim installed alongside, passing through to `pantheon` with deprecation notice
- PHP binary remains available as a separate install ("Terminus Classic")
- All documentation points to `pantheon`
- Deprecation notice appears when PHP Terminus is used
- Claude skill for plugin migration is available
- Proactive outreach to identified custom plugin customers begins

**Month 3 — First Checkpoint**
- Check telemetry for PHP Terminus usage (differentiate by VERSION or CONTEXT_APP_VERSION in Segment data)
- Follow up with any customers still on PHP who haven't responded to outreach
- Publish migration progress update

**Month 6 — PHP Enters Maintenance-Only**
- No new features or bug fixes for PHP Terminus
- Security patches only
- Deprecation warnings become more prominent
- Escalate remaining stragglers via CSMs or account teams

**Month 12 — End of Life**
- PHP binary is no longer published or supported
- Download links removed
- Remaining users get a clear error pointing them to `pantheon`

### Alternative Timelines Considered

**Option A: Hard cutover (0-day):** Simplest to execute, highest risk. Likely surprise breakage for customers with plugins not visible in telemetry. Not recommended.

**Option B: 6-month overlap:** Adequate for most customers but tight for enterprise accounts with quarterly planning cycles. Viable if time pressure is high.

**Option D: 18+ months, feature-gated:** Gentlest approach but creates no urgency. Long tail of PHP usage may persist for years. Not recommended unless contractual obligations prevent deprecation.

### Why 12 Months

- Enterprise customers have quarterly planning cycles. A plugin rewrite might not make it onto a team's roadmap until the next quarter even if communicated today.
- Three natural checkpoints allow measuring progress and adjusting strategy.
- The cost is low — the PHP binary is frozen. No new features, just hosting the existing artifact.
- The `terminus` shim for the rename can follow the same 12-month timeline or remain longer since it costs essentially nothing.

---

## 10. Open Questions

1. **Go Plugin SDK design:** What should the plugin interface look like? If the client-server architecture (Section 8) is adopted, this becomes a protocol specification rather than a compiled Go interface. If a monolithic CLI is preferred, should plugins be compiled Go binaries, scripts communicating over a protocol (like MCP), or something else?

2. **Build Tools migration:** Build Tools is being deprecated. What is the migration path for the ~400 users currently relying on `build:*` commands? Is this handled separately from the Terminus Go migration?

3. **Telemetry blind spots:** Can we add Terminus-command-level tracking (not just API-request-level) before the Go launch to better understand plugin usage? This would capture Tier 2 plugins that shell out to external tools without making additional API calls.

4. **Customer identification:** Can we join the `USER_ID` from the Segment telemetry to Salesforce account data to identify which enterprise accounts are running custom plugins and prioritize outreach?

5. **`terminus` shim longevity:** Should the `terminus` shim persist beyond the 12-month PHP deprecation window? It costs almost nothing to maintain and prevents breakage in old scripts/documentation indefinitely.

6. **Claude skill scope:** Should the Claude skill only handle PHP-to-Go plugin migration, or should it also assist with the `terminus`-to-`pantheon` rename in CI/CD pipelines and scripts?

7. **Client-server architecture:** Should the Go rewrite adopt the client-server separation described in Section 8? This is an architectural decision that affects the plugin SDK design, multi-surface support (CLI, IDE, web), and the long-term extensibility of the platform. Key sub-questions: should the server run as a daemon or be spawned per command? What protocol should be used for client-server and plugin communication? How does this interact with the npm distribution strategy for JS-native developers?

---

## Appendix A: Data Sources

- **Plugin architecture:** Source code analysis of `pantheon-systems/terminus` repository (branch `merge-secrets-manager-plugin`)
- **Community plugins:** Source code analysis of 16 plugins from `terminus-plugin-project` GitHub organization
- **Telemetry:** `STAGE_SEGMENT.PANTHEONAPI_TERMINUS_PRODUCTION.TERMINUS_API_REQUEST` (Snowflake), queried 2026-03-18
- **Core command list:** 143 commands extracted from `@command` annotations in `src/Commands/**/*.php`
- **Aggregate tables consulted:** `ANALYTICS.PANTHEON_PLATFORM.AGG_TERMINUS_REQUEST_SITE`, `ANALYTICS.PANTHEON_PLATFORM.AGG_TERMINUS_REQUEST_DAILY`, `ANALYTICS.CUSTOMER.TERMINUS_API_REQUEST_AGG`

## Appendix B: Terminus Plugin Project Repositories Examined

All 25 repositories in the `terminus-plugin-project` GitHub organization:

terminus-autocomplete-plugin, terminus-backup-all-plugin, terminus-cantilever-plugin, terminus-code-plugin, terminus-config-export, terminus-developer-plugin, terminus-dibs-plugin, terminus-domain-challenge, terminus-filer-plugin, terminus-genie-plugin, terminus-hotfix-plugin, terminus-mustafa-plugin, terminus-omniscient-plugin, terminus-pancakes-plugin, terminus-plugin-help-plugin, terminus-replica-plugin, terminus-s3-sync-plugin, terminus-site-mount-plugin, terminus-site-status-plugin, terminus-snowman-plugin, terminus-stooges-plugin, terminus-upstream-deployment-plugin, terminus-upstream-testing-plugin, terminus-wraith-plugin

Of these, source code was examined in depth for: backup-all, filer, genie, s3-sync, wraith, hotfix, upstream-deployment.

Additional plugins examined from other repositories: `terminus-node-logs-plugin`, `terminus-repository-plugin` (both now merged into core).
