-- Ensure singleton sensor groups occur at most once per board.
-- Idempotent: running the migration again produces an empty mapping.

DROP TEMPORARY TABLE IF EXISTS `tmp_singleton_sensor_duplicates`;

CREATE TEMPORARY TABLE `tmp_singleton_sensor_duplicates` (
  `duplicateId` int NOT NULL PRIMARY KEY,
  `keeperId` int NOT NULL,
  KEY `idx_tmp_singleton_keeper` (`keeperId`)
) ENGINE=InnoDB;

INSERT INTO `tmp_singleton_sensor_duplicates` (`duplicateId`, `keeperId`)
SELECT `sensorConfig`.`id`, singletonGroups.`keeperId`
FROM `sensorConfig`
INNER JOIN `sensorTypes` ON `sensorTypes`.`id` = `sensorConfig`.`typId`
INNER JOIN (
  SELECT groupedSensors.`boardId`, groupedSensors.`typId`, MAX(groupedSensors.`id`) AS `keeperId`
  FROM `sensorConfig` groupedSensors
  INNER JOIN `sensorTypes` groupedTypes ON groupedTypes.`id` = groupedSensors.`typId`
  WHERE groupedSensors.`boardId` IS NOT NULL
    AND LOWER(REPLACE(REPLACE(REPLACE(groupedTypes.`name`, ' ', ''), '_', ''), '-', ''))
    IN ('gps', 'lora', 'otastatus', 'wakeup', 'wakeuplog', 'wakeupstan')
  GROUP BY groupedSensors.`boardId`, groupedSensors.`typId`
  HAVING COUNT(*) > 1
) singletonGroups
  ON singletonGroups.`boardId` = `sensorConfig`.`boardId`
 AND singletonGroups.`typId` = `sensorConfig`.`typId`
WHERE `sensorConfig`.`id` <> singletonGroups.`keeperId`;

UPDATE `sensorData`
INNER JOIN `tmp_singleton_sensor_duplicates` duplicateSensors
  ON duplicateSensors.`duplicateId` = `sensorData`.`sensorId`
SET `sensorData`.`sensorId` = duplicateSensors.`keeperId`;

SET @mds_has_sensor_permissions = (
  SELECT COUNT(*)
  FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sensor_permissions'
);

SET @mds_merge_sensor_permissions = IF(
  @mds_has_sensor_permissions > 0,
  'INSERT INTO `sensor_permissions` (`sensorId`, `userId`, `role`, `canView`, `canEdit`, `canReceiveAlerts`, `createdAt`, `updatedAt`) SELECT duplicateSensors.`keeperId`, permissions.`userId`, permissions.`role`, permissions.`canView`, permissions.`canEdit`, permissions.`canReceiveAlerts`, permissions.`createdAt`, permissions.`updatedAt` FROM `sensor_permissions` permissions INNER JOIN `tmp_singleton_sensor_duplicates` duplicateSensors ON duplicateSensors.`duplicateId` = permissions.`sensorId` ON DUPLICATE KEY UPDATE `canView` = GREATEST(`sensor_permissions`.`canView`, VALUES(`canView`)), `canEdit` = GREATEST(`sensor_permissions`.`canEdit`, VALUES(`canEdit`)), `canReceiveAlerts` = GREATEST(`sensor_permissions`.`canReceiveAlerts`, VALUES(`canReceiveAlerts`))',
  'DO 1'
);
PREPARE mds_merge_sensor_permissions_statement FROM @mds_merge_sensor_permissions;
EXECUTE mds_merge_sensor_permissions_statement;
DEALLOCATE PREPARE mds_merge_sensor_permissions_statement;

SET @mds_delete_sensor_permissions = IF(
  @mds_has_sensor_permissions > 0,
  'DELETE permissions FROM `sensor_permissions` permissions INNER JOIN `tmp_singleton_sensor_duplicates` duplicateSensors ON duplicateSensors.`duplicateId` = permissions.`sensorId`',
  'DO 1'
);
PREPARE mds_delete_sensor_permissions_statement FROM @mds_delete_sensor_permissions;
EXECUTE mds_delete_sensor_permissions_statement;
DEALLOCATE PREPARE mds_delete_sensor_permissions_statement;

DELETE channels
FROM `sensorChannelConfig` channels
INNER JOIN `tmp_singleton_sensor_duplicates` duplicateSensors
  ON duplicateSensors.`duplicateId` = channels.`sensorConfigId`;

DELETE configs
FROM `sensorConfig` configs
INNER JOIN `tmp_singleton_sensor_duplicates` duplicateSensors
  ON duplicateSensors.`duplicateId` = configs.`id`;

DROP TEMPORARY TABLE IF EXISTS `tmp_singleton_sensor_duplicates`;
