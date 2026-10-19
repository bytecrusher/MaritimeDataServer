<?php
require_once __DIR__ . '/../app/Application/SensorColor.php';
function colorCheck($condition, $message) { if (!$condition) throw new RuntimeException($message); }
foreach (array('#012345', '#abcdef', '#ABCDEF', '#000000', '#ffffff') as $color) {
    colorCheck(SensorColor::valid($color), 'Valid color rejected.');
    colorCheck(SensorColor::validatePost(array('ChartColor' => $color))['ChartColor'] === strtolower($color), 'Color normalization failed.');
}
foreach (array("');alert(1);//", '"></input><script>alert(1)</script>', '</script><script>alert(1)</script>',
    'url(javascript:alert(1))', '#123456" onfocus="alert(1)', "#123456\n", "#123456\0", '#fff', '', null, array('#abcdef')) as $color) {
    colorCheck(!SensorColor::valid($color), 'Unsafe/invalid color accepted.');
    colorCheck(SensorColor::forDisplay($color) === '#287d8e', 'Stored malicious color did not fall back safely.');
    foreach (array('ChartColor', 'GaugeRedAreaLowColor', 'GaugeRedAreaHighColor', 'GaugeNormalAreaColor', 'Value2GaugeNormalAreaColor') as $field) {
        try {
            SensorColor::validatePost(array($field => $color));
            throw new RuntimeException('Unsafe color write accepted.');
        } catch (InvalidArgumentException $expected) {}
    }
}
$root = dirname(__DIR__);
$form = file_get_contents($root . '/public/formSensors.php');
$gate = strpos($form, 'if (!myFunctions::canUserEditSensor(');
foreach (array('dbUpdateData::updateSensor(', 'dbUpdateData::updateSensorChannelModal(') as $write) {
    colorCheck($gate !== false && $gate < strpos($form, $write), 'Write before sensor authorization.');
}
colorCheck(strpos($form, 'mds_verify_csrf_token(') < $gate, 'Missing early CSRF check.');
colorCheck(substr_count($form, 'mds_h(SensorColor::forDisplay(') === 4, 'Unescaped form color sink.');
$internal = file_get_contents($root . '/public/internal.php');
colorCheck(str_contains($internal, 'data-chart-color="<?php echo htmlspecialchars('), 'Dashboard color must be an escaped data attribute, not executable JS.');
$js = file_get_contents($root . '/public/assets/js/dashboard.js');
colorCheck(str_contains($js, 'const chartColor = card.dataset.chartColor;'), 'Dashboard must read color as data.');
$updater = file_get_contents($root . '/app/Application/dbUpdateData.php');
foreach (array('updateSensor', 'updateSensorModal', 'updateSensorChannelModal') as $method) {
    colorCheck(str_contains($updater, 'function ' . $method . '($post) {' . "\n" . '    $post = SensorColor::validatePost($post);'), 'Missing early color validation: ' . $method);
}
$report = file_get_contents($root . '/docs/SECURITY_REVIEW_2026-10-05.md');
$ids = array('7ab0f71b','7506ea47','0ac4241a','60996b4d','e8872081','4020ea8c','631b0eca','d0a7a358',
    '034c26b4','74d4001b','be7a453c','a8359106','b298fb09','8bf524fe','b7f26871','227a86b4','654a25c8',
    '8580596e','de2d2599','6466c4d5','a916935c','2aa1208f','61313e1b','19478bbc','413ca2fe','58985364',
    '06cb36af','963a31bd','2be57f74');
preg_match_all('/^\| (?:High|Medium|Low|Informational) ([0-9a-f]+) \|/m', $report, $matches);
$listed = array_map(static fn($id) => substr($id, 0, 8), $matches[1]);
sort($ids); sort($listed);
colorCheck($ids === $listed && count($listed) === 29, 'Report must contain all 29 findings exactly once.');
echo "Sensor color security and 29-finding completeness tests passed.\n";
