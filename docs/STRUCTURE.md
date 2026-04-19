# Project Structure

The project now separates public entrypoints from internal application code.

## Directories

- `app/Application`
  - shared application logic and orchestration helpers
- `app/Domain`
  - board, sensor and user domain classes
- `app/Infrastructure`
  - configuration, database access and logging
- `app/Support`
  - utilities and helper libraries
- `src`
  - public webroot, UI pages, APIs, webhooks and legacy wrappers
- `var/log`
  - runtime-generated log files

## Compatibility

Legacy files in `src/frontend/func` and `src/config` remain as thin wrappers so the current public entrypoints keep working while the codebase migrates to the new structure.
