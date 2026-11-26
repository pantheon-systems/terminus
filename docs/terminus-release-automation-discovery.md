# Terminus release automation - discovery

# Tools to consider leveraging
- https://github.com/pantheon-systems/plugin-release-actions/
- https://github.com/googleapis/release-please
  - generate release PRs based on the conventionalcommits.org spec
- https://github.com/autotag-dev/autotag


# Notes
- Technical concern: A step a github action takes that would normally trigger another workflow if done by a human will not trigger any workflows.
- Releases should not be published automatically when code is merged to the default branch.
- 'autotag' convention is to decide at feature development time whether that specific feature necessitates a major or minor release (vs just patch), and then when release automation is triggered, the automation decides what the next version number will be based on what is included


# Theory
- Each PR targeting the default branch defines whether it requires major/minor vs patch
- Upon PR merge to default, automation drafts a new release PR
  - Updates the version in config/constants.yml
  - Updates the CHANGELOG
  - Maybe? Creates a draft CCB ticket??? - Phil says there should not be "Draft" CCB tickets lingering around long term
  - Phil's proposal: new branch is created from previous release, pushes it to github, creates new branch from 'dev' with above changes, PR targets previous release branch.
- When we decide we want to create a new release, we "just" merge that PR
  - Creates the new tag
  - Drafts the new release, with changelog
  - Creates new "back to dev" PR, bumping the version number to the `next.patch-dev` in config/constants.yml and CHANGELOG
    - potentially this could not be a PR, but a direct push to default (by automation)
- Publishing release (no longer draft) fires new automation
  - Creates the PR against homebrew
  - Creates a PR to update documentation repo
    - This step is complicated enough that it might be best to have an LLM do it.
  - Create a PR to draft a "release note" in documention repo
- Note: this would make the default branch
- Move the 2 approvals requirement from PR merge to default to the creation of a release
- Potential pitfall: if the release branch is long-living you'll need to preserve git history when you merge things back to "dev"
