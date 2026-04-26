<?php

require_once(__DIR__ . '/InternalPageService.php');
require_once(__DIR__ . '/dbUpdateData.php');
require_once(__DIR__ . '/NotificationService.php');
require_once(__DIR__ . '/../Infrastructure/Logging/writeToLogFunction.func.php');

class SettingsPageService
{
    public static function resolveCurrentUserFromSession()
    {
        return InternalPageService::resolveCurrentUserFromSession();
    }

    public static function handleSettingsSave($userObj, $config, $save, $post)
    {
        $result = array(
            'userObj' => $userObj,
            'success_msg' => null,
            'error_msg' => null,
        );

        if ($save === 'personal_data') {
            self::runUserSetter($userObj, 'setName', $post, 'setName not saved.', $result);
            self::runUserSetter($userObj, 'setUserTimeZone', $post, 'setUserTimeZone not saved.', $result);
            self::runUserSetter($userObj, 'setReceiveNotifications', $post, 'setReceiveNotifications not saved.', $result);
            $_SESSION['userObj'] = serialize($userObj);
            if (empty($result['error_msg'])) {
                $result['success_msg'] = 'User Data successfully saved.';
            }
        } elseif ($save === 'email') {
            self::handleEmailSave($userObj, $post, $result);
        } elseif ($save === 'password') {
            self::handlePasswordSave($userObj, $post, $result);
        } elseif ($save === 'dashboard_data') {
            try {
                $userObj->setDashboardUpdateInterval($post);
                $_SESSION['userObj'] = serialize($userObj);
                $result['success_msg'] = 'Dashboard settings successfully saved.';
            } catch (Exception $e) {
                $result['error_msg'] = 'Dashboard settings not saved.';
                self::logException($result['error_msg'], $e);
            }
        } elseif ($save === 'users') {
            try {
                dbUpdateData::updateUserStatus($post);
                $result['success_msg'] = 'User Status updated.';
            } catch (Exception $e) {
                $result['error_msg'] = 'Error on update User Status.';
                self::logException($result['error_msg'], $e);
            }
        } elseif ($save === 'addNewUserToBoard') {
            try {
                $addNewBoardToUserReturn = dbUpdateData::addNewBoardToUser($post, $userObj->getId());
                if ($addNewBoardToUserReturn) {
                    $result['success_msg'] = 'Board added successfully.<br>Wait for the next Lora update.';
                } else {
                    $result['error_msg'] = 'Board not added.';
                    writeToLogFunction::write_to_log($result['error_msg'], $_SERVER["SCRIPT_FILENAME"]);
                }
            } catch (Exception $e) {
                $result['error_msg'] = $e->getMessage();
                self::logException($result['error_msg'], $e);
            }
        } elseif ($save === 'serverSetting') {
            try {
                $config->saveServerSettings($post);
                $result['success_msg'] = 'Server settings saved.';
            } catch (Exception $e) {
                $result['error_msg'] = $e->getMessage();
                self::logException('Server settings not saved.', $e);
            }
        } elseif ($save === 'testMailUser') {
            if (NotificationService::sendTestEmail($userObj->getEmail(), $config, 'user-settings')) {
                $result['success_msg'] = 'Test email sent to your user address.';
            } else {
                $result['error_msg'] = 'Test email could not be sent.';
            }
        } elseif ($save === 'testMailSystem') {
            $targetEmail = trim((string)($config::$systemEmailAddress ?: $config::$adminEmailAddress ?: $userObj->getEmail()));
            if (NotificationService::sendTestEmail($targetEmail, $config, 'admin-settings')) {
                $result['success_msg'] = 'System test email sent.';
            } else {
                $result['error_msg'] = 'System test email could not be sent.';
            }
        }

        return $result;
    }

    public static function handleBoardFormSubmission($post)
    {
        $result = array(
            'success_msg' => null,
            'error_msg' => null,
        );

        if (isset($post['submit_formBoards'])) {
            try {
                dbUpdateData::updateBoard($post);
                $result['success_msg'] = 'Board successfully updated.';
            } catch (Exception $e) {
                $result['error_msg'] = 'Error while saving board changes.';
            }
        } elseif (isset($post['submit_formBoards_remove'])) {
            try {
                dbUpdateData::removeBoardOwner($post);
                $result['success_msg'] = 'Board successfully removed.';
            } catch (Exception $e) {
                $result['error_msg'] = 'Error while removing board.';
            }
        }

        return $result;
    }

    public static function buildPageData($userObj, $config)
    {
        $isAdmin = ((int) $userObj->getUserGroupAdmin() === 1);

        $notificationOverview = NotificationService::getNotificationStatusOverview($userObj->getId(), $isAdmin);

        return array(
            'demoMode' => (bool) $config::$demoMode,
            'showQrCode' => $config::$ShowQrCode,
            'apiKey' => $config::$apiKey,
            'sendEmails' => $config::$sendEmails,
            'myBoards' => $userObj->getMyBoardsAll(),
            'allBoards' => $isAdmin ? $userObj->getAllBoardsAdmin() : array(),
            'allUsers' => $isAdmin ? myFunctions::getAllUsers() : array(),
            'timeZones' => self::getTimeZoneList(),
            'currentLogContent' => self::getCurrentLogContent(),
            'isAdmin' => $isAdmin,
            'notificationOverview' => $notificationOverview,
        );
    }

    private static function handleEmailSave($userObj, $post, &$result)
    {
        $password = $post['password'] ?? '';
        $email = trim($post['email'] ?? '');
        $email2 = trim($post['email2'] ?? '');

        if ($email !== $email2) {
            $result['error_msg'] = 'The entered email addresses are not the same.';
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['error_msg'] = 'The entered email address are not valid.';
            return;
        }
        if (!password_verify($password, $userObj->getPassword())) {
            $result['error_msg'] = 'Wrong password.';
            return;
        }

        try {
            $userObj->setEmail($post);
            $_SESSION['userObj'] = serialize($userObj);
            $result['success_msg'] = 'E-Mail address successfully saved.';
        } catch (Exception $e) {
            $result['error_msg'] = 'Email address not successfully saved.';
            self::logException($result['error_msg'], $e);
        }
    }

    private static function handlePasswordSave($userObj, $post, &$result)
    {
        $passwordAlt = $post['passwordOld'] ?? '';
        $passwordNew = trim($post['passwordNew'] ?? '');
        $passwordNew2 = trim($post['passwordNew2'] ?? '');

        if ($passwordNew !== $passwordNew2) {
            $result['error_msg'] = 'The entered passwords are not the same.';
            return;
        }
        if ($passwordNew === '') {
            $result['error_msg'] = 'Empty password is not allowed.';
            return;
        }
        if (!password_verify($passwordAlt, $userObj->getPassword())) {
            $result['error_msg'] = 'Please enter correct password.';
            return;
        }

        $password_hash = password_hash($passwordNew, PASSWORD_DEFAULT);
        try {
            $userObj->setUserPassword($password_hash);
            $_SESSION['userObj'] = serialize($userObj);
            $result['success_msg'] = 'Password successfully saved.';
        } catch (Exception $e) {
            $result['error_msg'] = 'Password reset not successfully.';
            self::logException($result['error_msg'], $e);
        }
    }

    private static function runUserSetter($userObj, $method, $post, $logMessage, &$result)
    {
        try {
            $userObj->$method($post);
        } catch (Exception $e) {
            $result['error_msg'] = $e->getMessage();
            self::logException($logMessage, $e);
        }
    }

    private static function logException($message, $exception)
    {
        writeToLogFunction::write_to_log($message, $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log($exception->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
    }

    private static function getTimeZoneList()
    {
        $allZones = array();
        $timestamp = time();
        foreach (timezone_identifiers_list() as $key => $live_zone) {
            date_default_timezone_set($live_zone);
            $allZones[$key]['zone'] = $live_zone;
            $allZones[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
        }
        date_default_timezone_set('Europe/Berlin');
        return $allZones;
    }

    private static function getCurrentLogContent()
    {
        $format = 'log';
        date_default_timezone_set('Europe/Berlin');
        $months = array(1 => 'Januar', 2 => 'Februar', 3 => 'Maerz', 4 => 'April', 5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember');
        $month = date('n');
        $year = date('Y');
        $logDirectory = dirname(__DIR__, 2) . "/var/log";
        $filename = $logDirectory . "/log_" . $months[$month] . "_$year.$format";

        if (!is_dir($logDirectory)) {
            return 'Log directory not found: ' . $logDirectory;
        }

        if (!is_readable($logDirectory)) {
            return 'Log directory is not readable: ' . $logDirectory;
        }

        if (!file_exists($filename)) {
            return 'Log file not found.';
        }

        $data = file_get_contents($filename);
        if ($data === false) {
            return 'Error while opening Log file.';
        }

        return $data;
    }
}
