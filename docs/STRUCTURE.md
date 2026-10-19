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
- `public`
  - the only public document root for UI, APIs, webhooks, OTA and simulator tools
- `var/log`
  - runtime-generated log files

## Runtime Layout

The application now runs only from `public/`. Legacy `src/` entrypoints and duplicate compatibility wrappers have been removed as part of the webroot migration.
