# Netcup Deployment

The workflow `.github/workflows/deploy-netcup.yml` deploys the `development` branch to `https://mds-git.derguntmar.de`.

## Required GitHub secrets

Configure these secrets in GitHub under `Settings > Secrets and variables > Actions`:

- `NETCUP_SSH_HOST`: SSH host, for example `hosting157867.a2eee.netcup.net`
- `NETCUP_SSH_USER`: SSH user for the webhosting account
- `NETCUP_SSH_PRIVATE_KEY`: private SSH key with write access to the hosting target
- `NETCUP_SSH_PORT`: optional SSH port, defaults to `22`
- `DEPLOY_PHP_BINARY`: PHP binary on the hosting server, for example `/opt/plesk/php/8.3/bin/php`

## Optional GitHub variable or secret

- `NETCUP_DEPLOY_PATH`: deployment target directory. If unset, the workflow uses `/var/www/vhosts/hosting157867.a2eee.netcup.net/httpdocs/mds-git.derguntmar.de/`.

The web server root should point to the `public/` directory below this target path.

## Deployment behavior

- Runs automatically on pushes to `development`.
- Can also be started manually via `Actions > Deploy to Netcup mds-git > Run workflow`.
- Installs Composer production dependencies in GitHub Actions before upload.
- Uses rsync over SSH and deletes files on the server that no longer exist in the repository.
- Runs a PHP syntax check on the server with `DEPLOY_PHP_BINARY` after upload.
- Keeps runtime data out of deployment: `.env`, `config.json`, `config/config.json`, SQL dumps, logs, status JSON files and OTA runtime files are excluded.
