# Project Structure

The project now separates public entrypoints from internal application code.

## Directories

- `bootstrap`
  - startup bootstrap shared by web and CLI entrypoints
- `config`
  - non-public configuration files
- `app/Application`
  - shared application logic and orchestration helpers
- `app/Bootstrap`
  - namespaced bootstrap and path helper classes
- `app/Domain`
  - board, sensor and user domain classes
- `app/Http`
  - browser APIs, ingest endpoints and webhook handlers
- `app/Infrastructure`
  - configuration, database access and logging
- `app/Support`
  - utilities and helper libraries
- `src`
  - public webroot, UI pages, APIs, webhooks and legacy wrappers
- `public`
  - preferred document root for modern deployment
- `var/log`
  - runtime-generated log files

## Compatibility

Public entrypoints remain under `src/` and `public/`, but the old internal helper wrappers under `src/frontend/func` and `src/config` have been removed in favor of direct bootstrapping into `app/`.
