<?php

require_once(__DIR__ . '/../Domain/User/user.class.php');
require_once(__DIR__ . '/../Domain/Board/board.class.php');
require_once(__DIR__ . '/myFunctions.func.php');

class InternalPageService
{
    public static function resolveCurrentUserFromSession()
    {
        if (isset($_SESSION['userId']) && (int) $_SESSION['userId'] > 0) {
            $sessionUserData = dbGetData::getUserById((int) $_SESSION['userId']);
            if (is_array($sessionUserData) && !empty($sessionUserData['email'])) {
                $currentUser = new user($sessionUserData['email']);
                $_SESSION['userObj'] = serialize($currentUser);
                return $currentUser;
            }
        }

        if (!isset($_SESSION['userObj'])) {
            return false;
        }

        $sessionUserObj = @unserialize($_SESSION['userObj'], ['allowed_classes' => ['user']]);
        if ($sessionUserObj instanceof user) {
            $_SESSION['userId'] = $sessionUserObj->getId();
            return $sessionUserObj;
        }

        return false;
    }

    public static function buildPageData($currentUser, $config)
    {
        $myBoardsIdList = $currentUser ? $currentUser->getMyBoardsAll() : array();
        if (!is_array($myBoardsIdList)) {
            $myBoardsIdList = array();
        }

        return array(
            'myBoardsIdList' => $myBoardsIdList,
            'boardObjsArray' => self::buildBoardObjects($myBoardsIdList),
            'mapPayload' => self::buildMapPayload($currentUser),
            'dashboardUpdateIntervalMs' => max(1000, (int) $currentUser->getDashboardUpdateInterval() * 10000),
            'demoMode' => (bool) $config::$demoMode,
            'showInstallAlert' => ((int) $currentUser->getUserGroupAdmin() === 1) && is_dir(__DIR__ . '/../../public/install'),
            'hasBoards' => !empty($myBoardsIdList),
        );
    }

    private static function buildBoardObjects($myBoardsIdList)
    {
        $boardObjsArray = array();
        foreach ($myBoardsIdList as $boardData) {
            if (!isset($boardData['id'])) {
                continue;
            }
            $boardObjsArray[] = new board($boardData['id']);
        }

        return $boardObjsArray;
    }

    private static function buildMapPayload($currentUser)
    {
        $mapBoardNames = array();
        $mapGpsData = array();

        if (!$currentUser) {
            return array(
                'boardNames' => $mapBoardNames,
                'gpsData' => $mapGpsData,
            );
        }

        $mapBoards = myFunctions::getMyBoards($currentUser->getId());
        foreach ($mapBoards as $mapBoard) {
            $gpsData = myFunctions::getAllGpsData($mapBoard['id']);
            if (!empty($gpsData) && $gpsData !== 0) {
                $boardId = (string) $mapBoard['id'];
                $mapBoardNames[$boardId] = $mapBoard['name'];
                $mapGpsData[$boardId] = $gpsData;
            }
        }

        return array(
            'boardNames' => $mapBoardNames,
            'gpsData' => $mapGpsData,
        );
    }
}
