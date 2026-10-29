---
contract_version: 2
kind: tool
service: terminus                   # a CLI shipped as a PHAR; nothing runs on Pantheon infrastructure

trigger: tag                        # pushing a tag runs the release jobs; merging to 4.x releases nothing
merged_implies_deployed: false      # 4.x carries <next>-dev between releases; only a tag publishes
other_paths_to_prod:
  - "pushing ANY tag (any commit, any branch, including -rc tags) runs the release jobs; the workflow does not check the tag is on 4.x or matches TERMINUS_VERSION"
  - "re-running a tag's workflow run re-uploads terminus.phar to that release and opens another Homebrew bump PR"   # inferred
  - "editing a GitHub release (prerelease flag, delete, asset re-upload) changes what self:update and the update notice offer, with no workflow run"
  - "a manual PR to pantheon-systems/homebrew-external Formula/terminus.rb changes what brew users get"

pipeline:
  system: github-actions
  workflow: ".github/workflows/4.x.yml"   # workflow name "Terminus 4.x"; select it by file name, the name is ambiguous with 3x.yml
  jobs_in_order: [checkout_build, functional, release, package_macos]
  typical_minutes: 45               # 4.3.0 ~40, 4.2.1 ~43, 4.2.2 ~63, 4.3.3 ~67 (tag push to Homebrew job end)
  minutes_by_change:
    checkout_build: 2               # lint, unit tests, `composer phar:build` (box)
    functional: 30                  # macOS, PHP 8.2 and 8.5, against live Pantheon; the two legs have run one after the other
    release: 1.5
    package_macos: 1
  watch: "Actions workflow 'Terminus 4.x' run for the tag ref: `gh run list -R pantheon-systems/terminus --workflow 4.x.yml --branch <tag>`; jobs 'Release' then 'Package a Homebrew release'"
  cancel_in_progress: true          # group ci-<ref>; a tag has its own ref, so branch pushes don't cancel a release run
  approval_gate: null               # no GitHub Environment; the human gate is CCB plus whoever pushes the tag

release:
  mechanism: "push tag <version> on the merged release commit on 4.x -> 'Release' job (softprops/action-gh-release) creates the GitHub release and uploads terminus.phar -> 'Package a Homebrew release' job (dawidd6/action-homebrew-bump-formula) opens a PR on pantheon-systems/homebrew-external bumping Formula/terminus.rb url+sha256"
  tag_scheme: "bare semver, no v prefix (4.3.3); pre-releases as 4.2.1-rc.1"
  prerelease_flag: "not set by the workflow; set it by hand on -rc releases right after publishing (4.2.1-rc.1 was left unflagged, so it was 'latest' and offered by self:update)"
  never: "move or delete a published tag (Packagist and Composer caches key on it); publish a fixed patch instead"
  procedure: "the terminus-release skill in pantheon-skills (pantheon-engineering/skills/terminus-release); summary under 'How a change reaches production' below"

consumers:                          # how a release reaches users; each picks it up on its own schedule
  - {channel: "GitHub releases API 'latest'", pin: none, picked_up_by: "`terminus self:update` (stable releases only, skips prerelease-flagged) and the runtime update notice (cached 7 days, shown at most daily, tty only)"}
  - {channel: "Homebrew tap pantheon-systems/external", pin: "Formula/terminus.rb url + sha256", picked_up_by: "`brew upgrade terminus` after the bump PR merges"}
  - {channel: "Packagist pantheon-systems/terminus", pin: "consumers' composer constraints", picked_up_by: "Packagist crawls the tag; no workflow step"}   # inferred: crawl/webhook
  - {channel: "Pantheon CI images", pin: "varies", picked_up_by: "docker-build-tools-ci downloads 4.1.3 then runs self:update at image build; docker-php-ci uses the archived terminus-installer"}
  - {channel: "docs.pantheon.io", pin: "supported-versions table and a release note", picked_up_by: "hand-written docs PR in pantheon-systems/documentation"}

repo_properties:
  observed_at: 2026-10-29
  trust_level: TL5
  ccb_status: in-ccb
  owner: developer-experience
  repo_type: tool

gating_checks:
  required: ["Wiz IaC Scanner", "Wiz Vulnerability Scanner", "Wiz Data Scanner"]   # org ruleset 'TL5 - Non-Prod'
  approvals: {required: 1, code_owner: true, last_push_approval: false, strict_up_to_date: false, merge_queue: false}   # repo ruleset 'Protect *.x'
  run_but_not_required: ["Checkout & build Phar", "Functional testing matrix - PHP 8.2 on macos-latest", "Functional testing matrix - PHP 8.5 on macos-latest"]
  conditional: []
  release_gate: "the tag push is the deploy; nothing stops a tag on a red or unreviewed commit except process (CCB ticket approved before tagging)"

environments:
  - {name: github-release, target: "github.com/pantheon-systems/terminus/releases/<tag>", gates_prod: true}
  - {name: homebrew, target: "pantheon-systems/homebrew-external Formula/terminus.rb", gates_prod: false}   # runs after the GitHub release is already public

artifact:
  image: null                       # not a container
  file: "terminus.phar (single release asset)"
  tag_scheme: semver
  tag_mutable: false                # by convention; nothing enforces it
  retention: "GitHub release assets kept indefinitely"
  version_signal: "`php terminus.phar --version` prints 'Terminus <version>' from config/constants.yml TERMINUS_VERSION"
  tag_to_sha: "git rev-list -n1 <tag>"

runtime: []                         # nothing runs on our infrastructure; the PHAR runs on users' machines and in CI

deployed_when:                      # ALL must hold
  - "the tag's 'Release' job succeeded and `gh api repos/pantheon-systems/terminus/releases/latest --jq .tag_name` == <version> (not draft, not prerelease)"
  - "the release's terminus.phar asset downloads and `php terminus.phar --version` prints 'Terminus <version>'"
  - "a homebrew-external PR bumping Formula/terminus.rb to <version> is MERGED, and its sha256 == sha256 of the release asset"
  - "Packagist lists <version> (https://repo.packagist.org/p2/pantheon-systems/terminus.json)"   # TODO(verify-on-deploy): how long Packagist takes after the tag

health:
  soak_minutes: 0                   # no fleet to watch; health comes from smoke checks now and user reports over the following days
  baseline: "previous release's asset download_count and open-issue count; no request-level signal is available to this repo"
  checks:
    - name: release is latest and well formed
      kind: command
      run: "gh api repos/pantheon-systems/terminus/releases/latest --jq '{tag_name,prerelease,draft,assets:[.assets[]|{name,size,download_count}]}'"
      healthy: "tag_name == <version>, prerelease false, draft false, exactly one asset terminus.phar of similar size to the previous release (4.3.3: 12336985 bytes)"
    - name: phar runs and reports the version
      kind: command
      run: "curl -sSL -o /tmp/terminus.phar https://github.com/pantheon-systems/terminus/releases/download/<version>/terminus.phar && php /tmp/terminus.phar --version"
      healthy: "prints 'Terminus <version>' with exit 0 on a supported PHP"
    - name: homebrew formula matches the asset
      kind: command
      run: "shasum -a 256 /tmp/terminus.phar; gh api repos/pantheon-systems/homebrew-external/contents/Formula/terminus.rb -H 'Accept: application/vnd.github.raw' | grep -E 'url|sha256'"
      healthy: "formula url points at <version> and sha256 matches"
    - name: packagist has the tag
      kind: command
      run: "curl -s https://repo.packagist.org/p2/pantheon-systems/terminus.json | python3 -c 'import json,sys;print(json.load(sys.stdin)[\"packages\"][\"pantheon-systems/terminus\"][0][\"version\"])'"
      healthy: "== <version>"
    - name: user reports
      kind: delegate
      run: "new issues on github.com/pantheon-systems/terminus/issues since the release; support/Slack reports"   # TODO(owner): which Slack channel(s) get Terminus reports?
      healthy: "no new issue reporting a regression introduced in <version> in the first few days"
  known_baseline_errors:
    - "'Package a Homebrew release' job has failed on every release since 4.3.1 (4.3.1, 4.3.2, 4.3.3); the formula was bumped by hand each time. Logs have expired, so the cause is unknown"
  dashboards: []
  alerts: []
  # inferred: every API request carries User-Agent 'Terminus/<version> (...)', so adoption and error rate per version could be measured server side; not investigated

rollback:
  options:
    - when: "the release is broken and a fix will take more than a few hours"
      kind: retract-and-repin
      steps: |
        1. Mark the GitHub release as a pre-release (do not delete the tag):
           gh release edit <version> -R pantheon-systems/terminus --prerelease
           releases/latest then falls back to the previous stable release, so `self:update` stops offering <version>
           and the update notice points back within its 7-day cache.
        2. Homebrew: open a PR on pantheon-systems/homebrew-external setting Formula/terminus.rb url and sha256
           back to the previous release (capture_before_merge), get it merged.
        3. Tell users who already upgraded how to go back (below). Packagist keeps the tag; Composer users pin a constraint.
        4. Ship the fix as the next patch release (never re-use or move the tag).
      time_to_rollback_minutes: 15   # inferred: step 1 is one command; step 2 needs a tap PR review
    - when: otherwise
      kind: roll-forward
      steps: "fix on 4.x and release the next patch through the normal process (the usual practice: 4.1.6 -> 4.1.9 shipped within three weeks)"
      time_to_rollback_minutes: 60  # release pipeline alone is ~45 min, plus PR review and CCB
  not_restored:
    - "users who already ran self:update or brew upgrade keep the bad PHAR; self:update has no downgrade. Recovery: `curl -L https://github.com/pantheon-systems/terminus/releases/download/<previous>/terminus.phar --output <path to terminus>` or `brew upgrade terminus` after the formula revert"
    - "CI images built in the window (docker-build-tools-ci runs self:update at build) keep the bad version until rebuilt"
    - "Packagist and Composer lock files that resolved <version>"
  one_way_changes: []               # checked: a release changes no server-side state; config/data migrations in ~/.terminus would be the exception, check the changelog
  currently_blocked: null
  last_exercised: null
  capture_before_merge: "record the current latest release and formula before tagging: `gh api repos/pantheon-systems/terminus/releases/latest --jq .tag_name` and the url+sha256 lines of homebrew-external Formula/terminus.rb"
  who_can: ["@pantheon-systems/developer-experience (write on terminus for release edits)", "approvers on pantheon-systems/homebrew-external"]   # TODO(owner): who can approve and merge tap PRs?
  verify: "releases/latest returns the previous version; the formula url/sha256 match the previous release; `self:update` on <version> reports no newer stable release"   # TODO(verify-on-deploy): self:update behaviour when the current version is newer than latest

escalation:
  slack: null                       # TODO(owner): channel for Terminus release issues
  owner_team: developer-experience
  paged: false                      # inferred: no alerts exist
  issues: "https://github.com/pantheon-systems/terminus/issues"

preflight:
  - "gh auth status (needs write on pantheon-systems/terminus to edit releases; read on pantheon-systems/homebrew-external)"
  - "php on PATH (to run the downloaded PHAR's --version)"

automation_blockers:
  - blocker: "the Homebrew bump job has failed on the last three releases, so every release needs a hand-made tap PR"
    clears_when: "the 'Package a Homebrew release' job opens the tap PR successfully on a release"
  - blocker: "pre-release tags are published as full releases until someone flags them, so an -rc can briefly become 'latest' for self:update"
    clears_when: "the release job sets prerelease for tags with a semver pre-release suffix"
  - blocker: "the release starts from a human tag push with no check that the tagged commit is the approved, merged release commit on 4.x"
    clears_when: "the workflow verifies the tag is on 4.x and equals TERMINUS_VERSION before publishing"

verified:
  at: null
  by: null
  change: null
  running_digest: null              # for a tool: sha256 of the published terminus.phar
---

# Deployment

Terminus is a command-line tool. "Deploying" it means publishing a release: a git tag, a GitHub release with `terminus.phar` attached, and a Homebrew formula bump. Users then pick it up through `terminus self:update`, `brew upgrade`, or Composer. Nothing runs on Pantheon infrastructure, so there is nothing to roll out or watch in a cluster.

Merging to `4.x` does not release anything. Between releases `4.x` carries the next version with a `-dev` suffix in `config/constants.yml` and `CHANGELOG.md`.

## How a change reaches production

1. **Changes merge to `4.x`** through PRs. One approval from a code owner (`@pantheon-systems/developer-experience`) is required. Only the Wiz scanners are required checks: the build, unit and functional test jobs run on every push but don't block a merge, so check they're green.
2. **Release PR.** A branch `update_to_<version>` sets `TERMINUS_VERSION` in `config/constants.yml` and turns `## <version>-dev` into `## <version> - <date>` in `CHANGELOG.md`. It targets `4.x`.
3. **CCB.** Terminus is `in-ccb`. A CCB ticket must be approved before the release PR merges and the tag is pushed.
4. **Merge the release PR, then tag** the merge commit on `4.x` and push the tag (`git tag <version> && git push origin <version>`). Never create the release through the GitHub UI: the workflow creates it.
5. **The tag push runs `.github/workflows/4.x.yml`** ("Terminus 4.x"), about 45 minutes in total:
   - `checkout_build` (~2 min): lint, unit tests, builds `terminus.phar` with Box.
   - `functional` (~30 min): PHP 8.2 and 8.5 on macOS, against live Pantheon.
   - `release` (~1.5 min, tags only): creates the GitHub release and uploads `terminus.phar`. **The release is public from here.**
   - `package_macos` (~1 min, tags only): opens a PR on `pantheon-systems/homebrew-external` bumping `Formula/terminus.rb`. This has failed on every release since 4.3.1. If it fails, bump the formula by hand: `url` to the new release asset and `sha256` to the asset's checksum (see that repo's `RELEASING.md`).
6. **After the release**: paste the changelog section into the GitHub release notes, merge the Homebrew PR, flag `-rc` releases as pre-release, open the docs PR in `pantheon-systems/documentation` (supported-versions table and a release note), and open a "back to `<next>`-dev" PR on `4.x`.

The full step-by-step procedure is the `terminus-release` skill in `pantheon-skills` (`pantheon-engineering/skills/terminus-release`).

Other paths to production: any tag push runs the release jobs, whatever the commit. Editing a GitHub release (pre-release flag, delete, re-upload) changes what users are offered without a workflow run. A PR on the Homebrew tap changes what brew installs.

## How to confirm it deployed

All of these must hold:

```bash
# The release exists, is latest, and has exactly one asset
gh api repos/pantheon-systems/terminus/releases/latest \
  --jq '{tag_name,prerelease,draft,assets:[.assets[]|{name,size}]}'

# The asset runs and reports the version
curl -sSL -o /tmp/terminus.phar \
  https://github.com/pantheon-systems/terminus/releases/download/<version>/terminus.phar
php /tmp/terminus.phar --version          # Terminus <version>

# Homebrew matches the asset (after the tap PR merges)
shasum -a 256 /tmp/terminus.phar
gh api repos/pantheon-systems/homebrew-external/contents/Formula/terminus.rb \
  -H 'Accept: application/vnd.github.raw' | grep -E 'url|sha256'

# Packagist has the tag
curl -s https://repo.packagist.org/p2/pantheon-systems/terminus.json \
  | python3 -c 'import json,sys;print(json.load(sys.stdin)["packages"]["pantheon-systems/terminus"][0]["version"])'
```

These checks were run against 4.3.3 on 2026-10-29 and all returned the expected data. The formula's sha256 matched the asset.

TODO(verify-on-deploy): how long after the tag Packagist shows the new version.

## How to confirm it's healthy

There is no telemetry, dashboard or alert for Terminus. Health means:

- **At release time:** the checks above. `php terminus.phar --version` is the only direct check that the PHAR runs. Running `terminus auth:whoami` or another read-only command with the new PHAR is a reasonable extra smoke test if credentials are at hand.
- **In the following days:** new GitHub issues and support or Slack reports about the new version. A rising `download_count` on the release asset (from the first command above) shows uptake, not health.

The only known baseline failure is the Homebrew bump job (see above). A red overall run with a green `release` job means the GitHub release went out.

[inferred] Every API request carries `User-Agent: Terminus/<version> (...)`, so errors per Terminus version could be measured server side. Nobody has built that yet.

## Rollback

Terminus releases are normally fixed forward with a new patch release. Pull a release back only when it's broken badly and a fix will take more than a few hours.

**Before tagging**, record the rollback target: the current latest release (`gh api repos/pantheon-systems/terminus/releases/latest --jq .tag_name`) and the `url` and `sha256` lines of `Formula/terminus.rb` in `pantheon-systems/homebrew-external`.

**To retract a bad release:**

1. Mark it as a pre-release. Don't delete the tag or move it:
   ```bash
   gh release edit <version> -R pantheon-systems/terminus --prerelease
   ```
   `releases/latest` falls back to the previous stable release. `terminus self:update` (stable by default) skips pre-releases, and the update notice follows `releases/latest` (cached for up to 7 days per user).
2. Open a PR on `pantheon-systems/homebrew-external` setting `Formula/terminus.rb` `url` and `sha256` back to the recorded previous values, and get it merged.
3. Tell affected users how to go back. Neither `self:update` nor `brew upgrade` downgrades a version that is already installed:
   - PHAR: `curl -L https://github.com/pantheon-systems/terminus/releases/download/<previous>/terminus.phar --output <path to terminus>`
   - Homebrew: `brew update && brew reinstall terminus` once the formula revert has merged. [inferred]
   - Composer: pin the constraint to `<previous>`.
4. Ship the fix as the next patch version.

**Verify:** `releases/latest` returns the previous version and the formula points at it again. TODO(verify-on-deploy): what `self:update` says when the installed version is newer than the latest stable release.

**What this doesn't restore:** PHARs already installed by users, CI images built in the meantime (`docker-build-tools-ci` runs `self:update` at build time), and Composer lock files that resolved the bad version. A release changes no server-side state.

Rollback has never been exercised.

## Escalation

- Owner: `developer-experience` (code owners `@pantheon-systems/developer-experience`).
- Nobody is paged and there are no alerts.
- Issues: https://github.com/pantheon-systems/terminus/issues
- TODO(owner): the Slack channel for Terminus release problems.

## Suggested pipeline changes

- Fix the `package_macos` Homebrew bump job, which has failed on 4.3.1, 4.3.2 and 4.3.3.
- Set `prerelease: true` in the release job for tags with a semver pre-release suffix (`-rc.1` etc.). 4.2.1-rc.1 went out as a full release.
- Fail the release job unless the tag is on `4.x` and equals `TERMINUS_VERSION` in `config/constants.yml`.
- Put the changelog section into the GitHub release body automatically instead of pasting it by hand.
- Make the build and test jobs required checks on `4.x` (today only the Wiz scanners are required).
- `CONTRIBUTING.md` still says development happens on `master`.
