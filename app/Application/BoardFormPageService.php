<?php

require_once(__DIR__ . '/InternalPageService.php');
require_once(__DIR__ . '/../Domain/Board/board.class.php');
require_once(__DIR__ . '/myFunctions.func.php');

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

        return array(
            'boardId' => $boardId,
            'boardRow' => $boardRow,
            'boardObj' => new board($boardId),
            'sensors' => myFunctions::getAllSensorsOfBoardOld($boardId),
            'allUsers' => myFunctions::isUserAdmin((int)$currentUser->getId()) ? myFunctions::getAllUsers() : array(),
            'isAdmin' => myFunctions::isUserAdmin((int)$currentUser->getId()),
        );
    }
}
