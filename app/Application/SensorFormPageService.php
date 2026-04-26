<?php

require_once(__DIR__ . '/InternalPageService.php');
require_once(__DIR__ . '/myFunctions.func.php');

class SensorFormPageService
{
    public static function resolveCurrentUserFromSession()
    {
        return InternalPageService::resolveCurrentUserFromSession();
    }

    public static function buildPageData($sensorId, $channelNr = null, $isModal = false)
    {
        $sensorId = (int)$sensorId;
        if ($sensorId <= 0) {
            throw new InvalidArgumentException('Invalid sensor id.');
        }

        $sensorConfig = myFunctions::getSensorConfig($sensorId);
        if (!$sensorConfig) {
            throw new RuntimeException('Sensor not found.');
        }

        $sensorChannels = myFunctions::getSensorChannelsConfig($sensorId);
        $sensorType = myFunctions::getSensorType($sensorConfig['typId']);

        return array(
            'sensorConfig' => $sensorConfig,
            'sensorChannels' => $sensorChannels,
            'sensorType' => $sensorType,
            'allSensorTypes' => myFunctions::getAllSensorType(),
            'singleChannelConfig' => $isModal && $channelNr !== null ? myFunctions::getSensorChannelConfig($sensorId, (int)$channelNr) : null,
            'backBoardId' => $sensorConfig['boardId'] ?? null,
        );
    }
}
