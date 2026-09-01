# Steps to Cut a New Terminus Release

Terminus uses [Semantic Versioning](https://semver.org/) in the format of `major.minor.patch`.

If any of these steps should fail, abort the release-cutting process.

1. For major or minor version releases, the CMS Eco product owner will decide when the new version can be released. Patch releases can be shipped as soon as Engineering is satisfied.

2. Move into the Terminus project's root directory and check out your target branch (e.g. `4.x`). Update that branch by using `git pull` to ensure it is up to date.

3. Check out a new branch for the updated version you wish to release, giving it a descriptive name like `update_to_1.0.4`. Don't use the version tag as the branch name; tags and branches can't have the same name.

4. Update the version number and changelog in the codebase:
   1. TERMINUS_VERSION in `config/constants.yml`
   2. Update `## <version>-dev` in `CHANGELOG.md` to be `## <version_number> - <today's_date_in_big_endian>`
   3. **Important**: Ensure the changelog includes all customer-relevant changes since the previous release. Use `git log <previous_tag>..HEAD` to see what's changed and add missing PRs to the appropriate sections (Added, Fixed, Changed, etc.). Exclude internal-only changes like CI config updates, build script changes, or development tooling that don't affect end users.

5. Commit and push your update branch and open a pull request to merge into the target branch tested in **Step 2** above (e.g. `4.x`).
   1. Monitor the GitHub Actions tests that are automatically triggered by the PR:
      - Check test status: `gh pr checks <PR_NUMBER>`
      - View failed test logs: `gh run view <RUN_ID> --log-failed`
      - Some tests may fail due to infrastructure issues (e.g., "maximum number of Multidev environments reached") rather than code issues
      - If tests fail due to "maximum number of Multidev environments reached":
        - Check the test fixture site: `terminus multidev:list ci-terminus-composer`
        - Delete abandoned multidevs older than 1 day: `terminus multidev:delete ci-terminus-composer.<multidev_name> --delete-branch --yes`
        - Failed tests leave behind multidevs that accumulate over time and need manual cleanup
   2. Await 2 approvals on the PR, but do not merge yet.
   3. Proceed to next step, which can be _prepared_ before this PR is approved.  This PR and the CCB Jira ticket in the next step have inter-dependencies.  The exact sequence is:
      1. CCB Jira ticket requires _creation_ of this PR before it can be _drafted_.
      2. CCB Jira ticket is not _ready for review_ until tests have passed and this PR is _approved_, but _not merged_.
      3. This PR can be merged after CCB approval.

6. Draft a Jira ticket in "CCB" project to get approval from the "Change Control Board":
   1. **Create CCB Change Request** using Atlassian MCP tools with the following approach:

      **Important:** CCB textarea fields require Atlassian Document Format (ADF), which is a JSON structure. Plain text will be rejected with "Operation value must be an Atlassian Document Format" error.

      Use `mcp__atlassian__createJiraIssue` with these parameters:
      - **cloudId:** `12519b81-57b5-457a-bee5-0534438e646b`
      - **projectKey:** `CCB`
      - **issueTypeName:** `CCB Change Request`
      - **summary:** `Terminus X.Y.Z Release`
      - **description:** Markdown description of the release
      - **additional_fields:** JSON object with these fields:
        - `customfield_12050`: Squad - `{"id": "11058"}` (Developer Experience)
        - `customfield_13303`: Urgency - `{"id": "12071"}` (Normal)
        - `customfield_13304`: Service/Component - `"terminus"` (plain string)
        - `customfield_13313`: Feature Flag - ADF format (see example below)
        - `customfield_13314`: Business Justification - ADF format
        - `customfield_13315`: Risk of NOT deploying - ADF format
        - `customfield_13319`: Customer Impact Monitoring - ADF format
        - `customfield_13320`: Pantheon Internal Monitoring - ADF format
        - `customfield_13322`: Testing and Validation - ADF format
        - `customfield_13323`: Testing Signoff - `{"accountId": "712020:949f07e7-2e08-4b5a-a6f9-072413aa303d"}`
        - `customfield_13324`: Roll back plan - ADF format
        - `customfield_13325`: Code Review Links - ADF format
        - `customfield_13418`: Tested in Sandbox - `{"id": "12263"}` (No)
        - `customfield_13413`: Is this change risky - `{"id": "12261"}` (No)
        - `customfield_13479`: Customer site downtime - `{"id": "12395"}` (No)

      **ADF Format Example** (for text fields):
      ```json
      {
        "version": 1,
        "type": "doc",
        "content": [
          {
            "type": "paragraph",
            "content": [
              {
                "type": "text",
                "text": "Your text here"
              }
            ]
          }
        ]
      }
      ```

      For bullet lists in ADF:
      ```json
      {
        "version": 1,
        "type": "doc",
        "content": [
          {
            "type": "paragraph",
            "content": [{"type": "text", "text": "Introduction text"}]
          },
          {
            "type": "bulletList",
            "content": [
              {
                "type": "listItem",
                "content": [
                  {
                    "type": "paragraph",
                    "content": [{"type": "text", "text": "First item"}]
                  }
                ]
              },
              {
                "type": "listItem",
                "content": [
                  {
                    "type": "paragraph",
                    "content": [{"type": "text", "text": "Second item"}]
                  }
                ]
              }
            ]
          }
        ]
      }
      ```

   2. **Link to related tickets:** Add a comment to both CCB ticket and DEVX ticket using `mcp__atlassian__addCommentToJiraIssue` to cross-reference them.
   3. **Leave in Draft status** until ready for CCB review. Ready for CCB review after release PR is _approved_.
   4. **Manual creation URL (if needed):** [https://getpantheon.atlassian.net/secure/CreateIssue.jspa?issuetype=ccb-change-request&pid=14450](https://getpantheon.atlassian.net/secure/CreateIssue.jspa?issuetype=ccb-change-request&pid=14450)
   5. Proceed to next step, which can be prepared in advance of approvals.

7. Draft a new PR for documentation updates in the `github.com/pantheon-systems/documentation` repo.
   1. Acquire up-to-date documentation repo
      1. If the documentation repo is already visible in your workspace,
         1. Move into it
         2. Run `git fetch --all` to pull any recent changes.
         3. Run `git branch -vv` to check current branch and if `main` branch is behind at all.
         4. Run `git status` to check for clean working directory.  If current directory is unclean, `git stash` all changes with a brief message about when and why stash created.
         5. Change to `main` branch if not on it already.
         6. Ensure main branch fully matches remote by fast-forwarding if branch has not diverged.  If branch has diverged, move local version to a new branch and update `main` to exactly match origin:
            ```
            git checkout -b main-dirty-$(date +%Y%m%d)
            git checkout main
            git reset --hard origin/main
            ```
      2. If the documentation repo is not visible in your workspace, `git clone` it in your available workspace but avoid inadvertently committing as a git submodule.
   2. Create a new branch for this release.
   3. Update the supported versions page [https://github.com/pantheon-systems/documentation/edit/main/source/content/terminus/10-supported-terminus.md](https://github.com/pantheon-systems/documentation/edit/main/source/content/terminus/10-supported-terminus.md).
      1. Add the new **Version** at the top of the list. Fill in the **Release Date** with today's date. Leave the **EOL Date** blank.
      2. For the most recent release on your major branch, fill in the **EOL Date**. This will be today's date plus one year. Terminus versions EOL 1 year after they are no longer the latest.
      3. If applicable, remove all but one old versions from the list that are more than one year _past EOL_. If any versions are removed, the last line in the list should read `X.Y.Z or earlier`.
      4. If applicable, update the Terminus [PHP Version Compatibility Matrix](https://docs.pantheon.io/terminus/supported-terminus#php-version-compatibility-matrix). This will be contextually clear from the changes in this release.
   5. Create a new "Release Note" file in `source/releasenotes` for this release. You can use the previous release note as a starting point.
   6. Create a new draft PR
      1. Set the PR title to `Terminus Release X.Y.Z - Version Updates` and do the following in the PR template:
         1. Use this for your **Summary** section:
            `**[Version Updates](https://docs.pantheon.io/terminus/supported-terminus)** - Terminus Release X.Y.Z`
         2. Add a link to your Terminus Release PR.
         3. Include links to the DEVX release ticket and the CCB ticket.
         4. Create the PR as a draft.




8. Once both the terminus release PR and the CCB ticket are approved, trigger the release by merging and pushing a new tag. DO NOT create the release via the Github UI!
   1. Merge the release PR.
   2. On your local machine, checkout the target branch where PR merged to (e.g. `4.x`), and pull the new changes.
   3. Create a tag for the new release: `git tag 4.x.x`.
   4. Push the tag to Github: `git push origin 4.x.x`. Pushing this tag will trigger release automation.
   5. Find and monitor Github Actions workflow using `gh` or browsing to https://github.com/pantheon-systems/terminus/actions.  The workflow will take some time (~30+ minutes) to complete.
   6. The workflow will create a Github release automatically with the freshly built PHAR attached to it from the start. This prevents a gap period between release creation and PHAR attachment during which `terminus self:update` would be broken for users.
9. Once the release is published, edit the notes to include the changes from this release. It's convenient to simply copy the markdown from [https://github.com/pantheon-systems/terminus/edit/4.x/CHANGELOG.md](https://github.com/pantheon-systems/terminus/edit/4.x/CHANGELOG.md). Omit the release number and date when copying.
10. For releases `3.x` or later, a PR will be created in the [Homebrew formula](https://github.com/pantheon-systems/homebrew-external) repository. Find it and merge it to release to Homebrew.
11. Update the major terminus branch (e.g. `4.x`) "back to dev".  The new "dev version" number will be `a.b.c+1-dev`, where `a.b.c` is the version we just released.
   1. Create new branch `back_to_<dev_version>`
   2. Update version in `config/constants.yml`.
   3. In `CHANGELOG.md`, add a new heading above the latest release.
   4. Commit, push, and create a "Back to dev" PR.

## Notes

[Terminus' Usage of Semantic Versioning](https://getpantheon.atlassian.net/wiki/spaces/VULCAN/pages/154438719)
