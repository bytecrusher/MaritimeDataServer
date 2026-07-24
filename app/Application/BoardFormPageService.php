<?php

require_once(__DIR__ . '/InternalPageService.php');
require_once(__DIR__ . '/../Domain/Board/board.class.php');
require_once(__DIR__ . '/myFunctions.func.php');
require_once(__DIR__ . '/SensorCleanupService.php');

class BoardFormPageService
{
    public static function resolveCurrentUserFromSession()
    {
        return InternalPageService::resolveCurrentUserFromSession();
    }

    public static function buildPageData($currentUser, $boardId)
    {
        $boardId = (int) $boardId;
        if ($boardId <= 0) {
            throw new InvalidArgumentException('Invalid board id.');
        }

        $boardRow = myFunctions::getBoardById($boardId);
        if (!$boardRow) {
            throw new RuntimeException('Board not found.');
        }
        if (!myFunctions::canUserEditBoard((int)$currentUser->getId(), $boardId)) {
            throw new RuntimeException('Access denied.');
        }

        $sensorOverview = SensorCleanupService::getBoardSensorOverview($boardId);

        return array(
            'boardId' => $boardId,
            'boardRow' => $boardRow,
            'boardObj' => new board($boardId),
            'sensors' => myFunctions::getAllSensorsOfBoardOld($boardId),
            'sensorOverview' => $sensorOverview,
            'cleanupCandidateCount' => count(array_filter($sensorOverview, function ($row) {
                return !empty($row['cleanupCandidate']);
            })),
            'canCleanupSensors' => myFunctions::canUserManageBoardAccess((int)$currentUser->getId(), $boardId),
            'allUsers' => myFunctions::isUserAdmin((int)$currentUser->getId()) ? myFunctions::getAllUsers() : array(),
            'isAdmin' => myFunctions::isUserAdmin((int)$currentUser->getId()),
        );
    }

    public static function cleanupUnusedSensors($currentUser, $boardId, $staleDays = SensorCleanupService::DEFAULT_STALE_DAYS)
    {
        $boardId = (int)$boardId;
        if (!myFunctions::canUserManageBoardAccess((int)$currentUser->getId(), $boardId)) {
            throw new RuntimeException('Access denied.');
        }

        return SensorCleanupService::deleteUnusedBoardSensors(
            $boardId,
            (int)$currentUser->getId(),
            $staleDays
        );
    }
}
