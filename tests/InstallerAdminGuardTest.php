<?php
require_once __DIR__ . '/../app/Support/install_guard.func.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE users (id INTEGER, userGroupAdmin INTEGER)');
if (mds_install_has_admin($pdo)) {
    throw new RuntimeException('Fresh installations must allow the first administrator.');
}
$pdo->exec('INSERT INTO users VALUES (1, 0)');
if (mds_install_has_admin($pdo)) {
    throw new RuntimeException('Ordinary users are not administrators.');
}
$pdo->exec('INSERT INTO users VALUES (2, 1)');
if (!mds_install_has_admin($pdo)) {
    throw new RuntimeException('An existing administrator must block installer admin creation.');
}
$source = file_get_contents(__DIR__ . '/../public/install/api_createAdmin.php');
$guardPosition = strpos($source, 'mds_install_has_admin($pdo)');
$insertPosition = strpos($source, 'dbUpdateData::insertAdmin(');
if ($guardPosition === false || $insertPosition === false || $guardPosition > $insertPosition) {
    throw new RuntimeException('Admin guard must precede account creation.');
}
echo "Installer admin guard tests passed.\n";
