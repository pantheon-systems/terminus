# Claude Code Tips for Terminus Development

This file contains useful tips and patterns for working on the Terminus codebase with Claude Code.

## Testing Commands Locally

### Running Terminus Commands
- Use `terminus` directly (globally available, not `./terminus`)
- Example: `terminus upstream:list --format=json`
- Use `terminus help <command>` for detailed command information

### Viewing API Requests
```bash
# Use -vv flag to see API requests on stderr while getting command output on stdout
terminus -vv upstream:list | grep <filter>
```

### Testing Field Output
```bash
# Test default fields
terminus upstream:list --format=json | head -10

# Test specific fields
terminus upstream:list --fields=repository_url | grep <filter>

# Test field combinations
terminus upstream:list --fields=label,repository_url --format=json
```

## Testing and Linting

### Local Test Limitations
- Full test suite requires special infrastructure and doesn't run well locally
- Tests need Terminus PHAR file which may not be available
- Focus on manual testing and syntax verification for local development

### Code Quality Checks
```bash
# Always run linting before committing
composer code:lint

# Check PHP syntax
php -l path/to/file.php

# The linter will catch line length issues (120 char limit)
```

## Command Development Patterns

### Adding Fields to List Commands

When adding new fields to list commands (like `upstream:list`):

1. **Add field to `@field-labels`** in the command class:
   ```php
   * @field-labels
   *     existing_field: Existing Field
   *     new_field: New Field Label
   ```

2. **Consider backward compatibility** - use `@default-fields` to exclude new fields from default output:
   ```php
   * @default-fields existing_field1,existing_field2,existing_field3
   ```

3. **Follow existing patterns** - check similar commands like `upstream:info` or `site:list` for consistency

4. **Update tests** to verify both default behavior and explicit field selection

### Finding Implementation Patterns
- Use `grep` to find similar implementations: `rg "repository_url" --type php`
- Check how other commands handle similar fields (e.g., `site:list` vs `upstream:list`)
- Look at `@field-labels` and `@default-fields` annotations in existing commands

## Git Workflow

### Branch Management
- Feature branches should be named after Jira ticket numbers (e.g., `DEVX-5163`)
- Create separate branches for different types of changes (feature vs documentation)
- Main branch is `3.x` (not `main` or `master`)

### Committing Best Practices
- Only commit files related to your specific change
- Use descriptive commit messages that explain the "why"
- Include ticket references in commit messages
- Add Claude Code attribution:
  ```
  🤖 Generated with [Claude Code](https://claude.ai/code)

  Co-Authored-By: Claude <noreply@anthropic.com>
  ```

### Handling Unrelated Changes
- Use `git stash` to temporarily store unrelated changes
- Only stage specific files with `git add path/to/specific/file.php`
- Keep commits focused on single logical changes

## API Data Patterns

### Understanding Data Flow
- API endpoints often return more data than commands display by default
- Check API responses with verbose mode: `terminus -vv command`
- Model serialization in `serialize()` methods typically returns all API data
- Use `@field-labels` to make API fields available in command output

### Field Availability
- If API returns a field, it's usually available in the model's `serialize()` output
- Adding fields to command output often just requires updating `@field-labels`
- Use `--format=json` to see all available serialized data

## Documentation Integration

### Jira Integration
- Update tickets with implementation details and PR links
- Include testing results in ticket comments
- Transition tickets appropriately after implementation

### Pull Request Creation
```bash
# Use gh CLI for creating PRs
gh pr create --title "Title" --body "Detailed description with test plan"
```

## Common File Locations

- Commands: `src/Commands/<Category>/<Command>.php`
- Models: `src/Models/<Model>.php`
- Tests: `tests/Functional/<Category>CommandsTest.php`
- Traits: `src/Commands/<Trait>.php` (e.g., `StructuredListTrait.php`)