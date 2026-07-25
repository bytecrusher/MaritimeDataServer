<?php

require_once dirname(__DIR__) . '/app/Application/TtnPayloadDecoder.php';

function failTtnPayloadDecoderTest($message)
{
    fwrite(STDERR, 'FAILED: ' . $message . PHP_EOL);
    exit(1);
}

function assertTtnPayloadValue($expected, $actual, $message)
{
    if ($expected !== $actual) {
        failTtnPayloadDecoderTest($message . ' Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}

function putTtnUint16(array &$bytes, $offset, $value)
{
    $bytes[$offset] = $value & 0xff;
    $bytes[$offset + 1] = ($value >> 8) & 0xff;
}

function putTtnUint32(array &$bytes, $offset, $value)
{
    $bytes[$offset] = $value & 0xff;
    $bytes[$offset + 1] = ($value >> 8) & 0xff;
    $bytes[$offset + 2] = ($value >> 16) & 0xff;
    $bytes[$offset + 3] = ($value >> 24) & 0xff;
}

function encodeTtnTestPayload(array $bytes)
{
    return base64_encode(pack('C*', ...$bytes));
}

$measurements = array_fill(0, 51, 0);
$measurements[0] = 3;
putTtnUint16($measurements, 1, 321);
putTtnUint16($measurements, 3, 215);
putTtnUint16($measurements, 5, 10203);
$measurements[7] = 54;
putTtnUint16($measurements, 8, 118);
putTtnUint16($measurements, 10, 13625);
putTtnUint16($measurements, 12, 204);
putTtnUint32($measurements, 14, (-6796773) & 0xffffffff);
putTtnUint32($measurements, 18, 51193901);
$measurements[22] = 75;
$measurements[23] = 41;
$measurements[24] = 0xe5;
$measurements[25] = 92;
putTtnUint16($measurements, 26, 3621);
putTtnUint16($measurements, 28, 3673);
putTtnUint16($measurements, 30, 425);
putTtnUint16($measurements, 32, 12345);
putTtnUint16($measurements, 34, 123);
putTtnUint16($measurements, 36, 1310);
putTtnUint16($measurements, 38, 0xff9c);
putTtnUint16($measurements, 40, 2150);
putTtnUint32($measurements, 42, 1781517600);
putTtnUint32($measurements, 46, 1781518500);
$measurements[50] = 0x31;

$decodedMeasurements = TtnPayloadDecoder::decodeKnownPayload(1, encodeTtnTestPayload($measurements));
assertTtnPayloadValue('measurements', $decodedMeasurements['payloadType'] ?? null, 'FPort 1 payload type was not decoded.');
assertTtnPayloadValue(3, $decodedMeasurements['payloadSchema'] ?? null, 'FPort 1 schema was not decoded.');
assertTtnPayloadValue(13.625, $decodedMeasurements['voltage'] ?? null, 'Battery voltage was not decoded.');
assertTtnPayloadValue(-6.796773, $decodedMeasurements['longitude'] ?? null, 'Signed longitude was not decoded.');
assertTtnPayloadValue(51.193901, $decodedMeasurements['latitude'] ?? null, 'Latitude was not decoded.');
assertTtnPayloadValue('Wakeup Timer', $decodedMeasurements['wakeupCause'] ?? null, 'Wakeup cause was not decoded.');
if (array_key_exists('macAddress', $decodedMeasurements)) {
    failTtnPayloadDecoderTest('Schema 3 measurements must not manufacture a MAC address from measurement bytes.');
}

$device = array_fill(0, 34, 0);
$device[0] = 1;
$device[1] = 0x3f;
$device[2] = 18;
$device[3] = 5;
putTtnUint16($device, 4, 15);
$device[6] = 24;
$device[7] = 1;
$device[8] = 3;
$device[9] = 10;
$device[10] = 8;
$device[12] = 1;
$device[13] = 2;
foreach (str_split('V1.15w') as $index => $character) {
    $device[14 + $index] = ord($character);
}
$device[24] = 0x94;
$device[25] = 0xb9;
$device[26] = 0x7e;
$device[27] = 0xfe;
$device[28] = 0xf5;
$device[29] = 0x40;

$decodedDevice = TtnPayloadDecoder::decodeKnownPayload(2, encodeTtnTestPayload($device));
assertTtnPayloadValue('deviceConfig', $decodedDevice['payloadType'] ?? null, 'FPort 2 payload type was not decoded.');
assertTtnPayloadValue('V1.15w', $decodedDevice['firmwareVersion'] ?? null, 'Firmware version was not decoded.');
assertTtnPayloadValue('94:B9:7E:FE:F5:40', $decodedDevice['macAddress'] ?? null, 'Device MAC address was not decoded.');
assertTtnPayloadValue('WifiFirst', $decodedDevice['transmitPriority'] ?? null, 'Transmit priority was not decoded.');

assertTtnPayloadValue(null, TtnPayloadDecoder::decodeKnownPayload(1, base64_encode('legacy')), 'Unknown payload must remain on the TTN decoder fallback.');

fwrite(STDOUT, "TtnPayloadDecoder tests passed.\n");
