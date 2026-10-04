-- Roles and board/sensor permissions for Maritime Data Server.
-- This migration is idempotent and can be executed multiple times.

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `isGlobal` tinyint NOT NULL DEFAULT '1',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `userId` int NOT NULL,
  `roleId` int NOT NULL,
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_user_roles_user_role` (`userId`, `roleId`),
  KEY `idx_user_roles_roleId` (`roleId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `board_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `boardId` int NOT NULL,
  `userId` int NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'observer',
  `canView` tinyint NOT NULL DEFAULT '1',
  `canEdit` tinyint NOT NULL DEFAULT '0',
  `canManageUsers` tinyint NOT NULL DEFAULT '0',
  `canReceiveAlerts` tinyint NOT NULL DEFAULT '1',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_board_permissions_board_user` (`boardId`, `userId`),
  KEY `idx_board_permissions_userId` (`userId`),
  KEY `idx_board_permissions_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sensor_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sensorId` int NOT NULL,
  `userId` int NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'observer',
  `canView` tinyint NOT NULL DEFAULT '1',
  `canEdit` tinyint NOT NULL DEFAULT '0',
  `canReceiveAlerts` tinyint NOT NULL DEFAULT '1',
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedAt` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sensor_permissions_sensor_user` (`sensorId`, `userId`),
  KEY `idx_sensor_permissions_userId` (`userId`),
  KEY `idx_sensor_permissions_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permission_audit_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `actorUserId` int DEFAULT NULL,
  `targetUserId` int DEFAULT NULL,
  `resourceType` varchar(20) NOT NULL,
  `resourceId` int NOT NULL,
  `action` varchar(30) NOT NULL,
  `oldValue` text DEFAULT NULL,
  `newValue` text DEFAULT NULL,
  `createdAt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_permission_audit_resource` (`resourceType`, `resourceId`),
  KEY `idx_permission_audit_actor` (`actorUserId`),
  KEY `idx_permission_audit_target` (`targetUserId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`name`, `description`, `isGlobal`) VALUES
  ('admin', 'System administrator', 1),
  ('user', 'Regular authenticated user', 1),
  ('owner', 'Board or sensor owner', 0),
  ('observer', 'Read-only observer with notifications', 0)
ON DUPLICATE KEY UPDATE
  `description` = VALUES(`description`),
  `isGlobal` = VALUES(`isGlobal`);

INSERT IGNORE INTO `user_roles` (`userId`, `roleId`)
SELECT `users`.`id`, `roles`.`id`
FROM `users`
INNER JOIN `roles`
  ON `roles`.`name` = CASE WHEN `users`.`userGroupAdmin` = 1 THEN 'admin' ELSE 'user' END;

INSERT INTO `board_permissions` (`boardId`, `userId`, `role`, `canView`, `canEdit`, `canManageUsers`, `canReceiveAlerts`)
SELECT `id`, `ownerUserId`, 'owner', 1, 1, 1, 1
FROM `boardConfig`
WHERE `ownerUserId` IS NOT NULL AND `ownerUserId` > 0
ON DUPLICATE KEY UPDATE
  `role` = 'owner',
  `canView` = 1,
  `canEdit` = 1,
  `canManageUsers` = 1,
  `canReceiveAlerts` = 1;
