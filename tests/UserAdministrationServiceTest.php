<?php

require_once dirname(__DIR__) . '/app/Application/UserAdministrationService.php';

function failUserAdministrationServiceTest($message)
{
    fwrite(STDERR, 'FAILED: ' . $message . PHP_EOL);
    exit(1);
}

$normalized = UserAdministrationService::normalizeDeletionRequest(1, array('4', 2, '2', 0, -1, 'invalid'));
if ($normalized !== array(2, 4)) {
    failUserAdministrationServiceTest('User ids were not normalized and deduplicated.');
}

try {
    UserAdministrationService::normalizeDeletionRequest(2, array(2, 3));
    failUserAdministrationServiceTest('Self-deletion was accepted.');
} catch (RuntimeException $e) {
    // Expected.
}

try {
    UserAdministrationService::normalizeDeletionRequest(1, array());
    failUserAdministrationServiceTest('An empty deletion request was accepted.');
} catch (InvalidArgumentException $e) {
    // Expected.
}

echo "UserAdministrationService tests passed.\n";
