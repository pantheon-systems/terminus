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
   2. Get approval from someone authorized to approve Terminus PRs and merge this into your target branch.

6. ~~Test your work to ensure that Composer won't have trouble installing the application.~~
   1. ~~Create a new directory anywhere but in Terminus and change into it before running the test.~~
   2. ~~Run this command. Answer `n` (No) if prompted to use another composer.json file in a directory above yours.~~
      ```
      composer require pantheon-systems/terminus:4.x-dev
      ```
   3. ~~If there are no errors, you can delete the directory and proceed.~~

7. Create a CCB Ticket and get approval from the change committee:
   1. **Create CCB Change Request** using Atlassian MCP tools (preferred) or web interface:
      - **Project:** CCB
      - **Issue Type:** CCB Change Request
      - **Squad:** Developer Experience (11058)
      - **Service/Component:** terminus
      - **Summary:** Terminus X.Y.Z Release
      - **Business Justification:** Describe customer benefits
      - **Testing:** Reference GitHub Actions + manual testing
      - **Rollback Plan:** Emergency hotfix release to previous version
      - **Code Review Link:** GitHub PR URL
      - **Risk Assessment:** Typically low risk for additive features
      - **Monitoring Plans:** User feedback in Slack/support tickets (exclude GitHub download monitoring)
      - **Feature Flag:** None (client-side tool)
      - **Urgency:** Normal
      - **Tested in Sandbox:** No
   2. **Link to related tickets:** Add comment linking to original DEVX ticket
   3. **Leave in Draft status** until ready for CCB review
   4. **Manual creation URL (if needed):** [https://getpantheon.atlassian.net/secure/CreateIssue.jspa?issuetype=ccb-change-request&pid=14450](https://getpantheon.atlassian.net/secure/CreateIssue.jspa?issuetype=ccb-change-request&pid=14450)

8. Once approved, Create a tag:
   1. On your local machine, checkout the target branch (e.g. `4.x`) and pull down the latest changes.
   2. Create a tag for the new release: `git tag 4.x.x`
   3. Push the tag to Github: `git push origin 4.x.x.`
   4. Browse to https://github.com/pantheon-systems/terminus/actions where you can see a GitHub Actions workflow has kicked off.
   5. This will create a release automatically with the PHAR attached from the start (which prevents a broken `self:update` gap period).

9. Once the release is published, edit the notes to include the changes from this release. It's convenient to copy the markdown from [https://github.com/pantheon-systems/terminus/edit/4.x/CHANGELOG.md](https://github.com/pantheon-systems/terminus/edit/4.x/CHANGELOG.md). Omit the release number and date when copying.

10. For releases `3.x` or later, a PR will be created in the [Homebrew formula](https://github.com/pantheon-systems/homebrew-external) repository. Take a look to it and merge it to release to Homebrew.

11. Update your major branch's version number to `a.b.c+1-dev`, where `a.b.c` is the latest release's version:
    1. In `config/constants.yml`, change the value
    2. In `CHANGELOG.md`, add a new heading above your most recent release. It should also be in the format of `a.b.c+1-dev`.

12. Open a PR against https://github.com/pantheon-systems/documentation to update the supported versions page [https://github.com/pantheon-systems/documentation/edit/main/source/content/terminus/10-supported-terminus.md](https://github.com/pantheon-systems/documentation/edit/main/source/content/terminus/10-supported-terminus.md).
    1. Add the new **Version** at the top of the list. Fill in the **Release Date** with today's date. Leave the **EOL Date** blank.
    2. For the most recent release on your major branch, fill in the **EOL Date**. This will be today's date plus one year.
    3. If applicable, remove all but one old versions per major branch that are more than one year past EOL from the list. If some of the versions for a major branch have been removed from the list, the last line in the list should read `X.Y.Z or earlier`.
    4. If applicable, update the Terminus [PHP Version Compatibility Matrix](https://docs.pantheon.io/terminus/supported-terminus#php-version-compatibility-matrix).
    5. Set the PR title to `Terminus Release X.Y.Z - Version Updates` and do the following in the PR template:
       1. Use this for your **Summary** section:
          `**[Version Updates](https://docs.pantheon.io/terminus/supported-terminus)** - Terminus Release X.Y.Z`
       2. In the **Effect** section, add a link to your Terminus Release PR as the only bullet under **The following changes are already committed.**
       3. Remove the **Remaining Work and Prerequisites** section.
       4. In the **Release** section, fill the **When Ready** box with an `X` and remove the line **After date: $DATE.**
       5. Create the PR as ready for approval (not a draft).

13. Create a separate PR for the release notes:
    1. Create a new file in `source/releasenotes` for this release. You can use [this example](https://github.com/pantheon-systems/documentation/blob/main/source/releasenotes/2024-12-05-terminus-361.md?plain=1) as a starting point.
    2. Set the PR title to `Terminus Release X.Y.Z - Release Notes`
    3. Follow the same PR template guidelines as Step 12.

You can also use this as the contents for that PR description (remember to update the version and PR):

```
## Summary

**[Supported Terminus and PHP Versions](https://docs.pantheon.io/terminus/supported-terminus)** - Include Terminus 4.1.0

Also, include new release note.
```

## See Also

[Terminus' Usage of Semantic Versioning](https://getpantheon.atlassian.net/wiki/spaces/VULCAN/pages/154438719)
