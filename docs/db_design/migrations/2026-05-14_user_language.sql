-- Adds the per-user UI language preference used by the lightweight i18n layer.

SET @db_name := DATABASE();

SET @sql := IF(
  EXISTS (
    SELECT 1
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'language'
  ),
  'SELECT ''users.language already exists''',
  'ALTER TABLE `users` ADD COLUMN `language` varchar(5) NOT NULL DEFAULT ''en'' AFTER `Timezone`'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `users`
SET `language` = 'en'
WHERE `language` IS NULL OR `language` = '';
