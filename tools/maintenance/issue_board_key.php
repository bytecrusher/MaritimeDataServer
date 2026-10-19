<?php
// Operator-only provisioning: never reachable through an HTTP request.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once dirname(__DIR__, 2) . '/app/Infrastructure/Database/dbConfig.func.php';
$id = filter_var($argv[1] ?? '', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
if (!$id) { fwrite(STDERR, "Usage: php tools/maintenance/issue_board_key.php BOARD_ID\n"); exit(1); }
$pdo = dbConfig::getInstance();
$query = $pdo->prepare('SELECT id FROM boardConfig WHERE id = ?');
$query->execute(array($id));
if (!$query->fetchColumn()) throw new RuntimeException('Board not found.');
$path = dirname(__DIR__, 2) . '/config/config.json';
$lock = fopen($path . '.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock configuration.');
try {
    $config = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    $token = bin2hex(random_bytes(16)); // 128 bits, compatible with existing 32-character device fields.
    $config['boardApiKeyHashes'][(string)$id] = hash('sha256', $token);
    $temp = tempnam(dirname($path), '.board-key-');
    chmod($temp, 0600);
    if (file_put_contents($temp, json_encode($config, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false || !rename($temp, $path)) {
        throw new RuntimeException('Cannot save board credential.');
    }
    echo "New board API key (previous board key revoked): " . $token . "\n";
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
