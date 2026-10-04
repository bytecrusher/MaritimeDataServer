# Netcup Deployment

The workflow `.github/workflows/deploy-netcup.yml` deploys the `development` branch to `https://mds-git.derguntmar.de`.

## Required GitHub secret

Configure these secrets in GitHub under `Settings > Secrets and variables > Actions`:

- `NETCUP_SSH_PRIVATE_KEY`: private SSH key with write access to the hosting target

## Required GitHub variables or secrets

These values can be configured as repository variables or repository secrets:

- `NETCUP_SSH_HOST`: SSH host, for example `hosting157867.a2eee.netcup.net`
- `NETCUP_SSH_USER`: SSH user for the webhosting account

## Optional GitHub variables or secrets

- `NETCUP_DEPLOY_PATH`: deployment target directory. If unset, the workflow uses `/var/www/vhosts/hosting157867.a2eee.netcup.net/httpdocs/mds-git.derguntmar.de/`.
- `NETCUP_SSH_PORT`: SSH port, defaults to `22`
- `DEPLOY_PHP_BINARY`: preferred PHP binary on the hosting server. If it is not executable, the workflow falls back to common Plesk PHP paths and `php` from `PATH`.

The web server root should point to the `public/` directory below this target path.

## Deployment behavior

- Runs automatically on pushes to `development`.
- Can also be started manually via `Actions > Deploy to Netcup mds-git > Run workflow`.
- Installs Composer production dependencies in GitHub Actions before upload.
- Uses `tar`, `scp` and `ssh`; server-side `rsync` is not required.
- Deletes deployed application files on the server that no longer exist in the repository while preserving runtime configuration and data.
- Runs a PHP syntax check on the server with `DEPLOY_PHP_BINARY` after upload.
- Keeps runtime data out of deployment: `.env`, `config.json`, `config/config.json`, SQL dumps, logs, status JSON files and OTA runtime files are excluded. Existing files below `var/ota/` remain in place; `public/ota/bin/` is copied aside before replacing `public/` and restored afterwards.
