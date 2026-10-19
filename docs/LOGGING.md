# Application logging

MDS writes application logs to `var/log`. A new base file is selected each month, for example `log_Juli_2026.log`.

## Rotation and retention

- `logMaxFileSizeMb` limits the size of an individual log file. The default is 10 MB.
- Once the limit is reached, the current file is renamed with a timestamp suffix and logging continues in a fresh monthly file.
- `logRetentionDays` controls how long current and rotated `.log` files remain. The default is 90 days.
- At most once per day, the first subsequent log write removes files older than the configured retention period.
- Rotation and cleanup use a shared file lock so concurrent PHP requests cannot rotate or delete the same file simultaneously.

Both settings are available to administrators under `Settings -> Server Setting` and are stored in `config/config.json`.

Environment variables `MDS_LOG_MAX_FILE_SIZE_MB` and `MDS_LOG_RETENTION_DAYS` can override the file configuration for a deployment.

## Scheduled cleanup

Automatic cleanup during logging is sufficient for active installations. A daily scheduled task can additionally run:

```bash
/opt/plesk/php/8.3/bin/php /absolute/path/to/tools/maintenance/log_cleanup.php
```

The existing privacy maintenance job also uses the same cleanup mechanism:

```bash
/opt/plesk/php/8.3/bin/php /absolute/path/to/tools/maintenance/privacy_cleanup.php
```
