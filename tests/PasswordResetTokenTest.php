<?php
require_once __DIR__ . '/../app/Application/dbUpdateData.php';

// Exercise the real lookup without a production database or credentials.
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->sqliteCreateFunction('NOW', static fn() => '2026-10-04 12:00:00');
$pdo->exec('CREATE TABLE users (id INTEGER, passwordCode TEXT, passwordCodeTime TEXT)');
$property = new ReflectionProperty(dbConfig::class, 'pdo');
$property->setValue(null, $pdo);
$insert = $pdo->prepare('INSERT INTO users VALUES (?, ?, ?)');
$insert->execute(array(1, hash('sha256', 'valid-token'), '2026-10-04 13:00:00'));
$insert->execute(array(2, hash('sha256', 'expired-token'), '2026-10-04 11:00:00'));
$insert->execute(array(3, '', '2026-10-04 13:00:00'));
$insert->execute(array(4, 'legacy-token', '2026-10-04 13:00:00'));
$insert->execute(array(5, 'expired-legacy', '2026-10-04 11:00:00'));
$insert->execute(array(6, null, null));
foreach (array(
    array('valid-token', 1, true), array('valid-token', 2, false),
    array('wrong-token', 1, false), array('expired-token', 2, false),
    array('', 3, false), array(' ', 3, false), array(null, 3, false),
    array(array('token'), 1, false), array('legacy-token', 4, true),
    array('expired-legacy', 5, false), array('', 6, false),
) as [$token, $id, $expected]) {
    if ((bool)dbUpdateData::readUserPasswordCode($token, $id) !== $expected) {
        throw new RuntimeException('Unexpected reset-token validation for user ' . $id);
    }
}
echo "Password reset token tests passed.\n";
