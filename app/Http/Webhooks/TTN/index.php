<?php
/*
 *  Version 1.1
 *  Created 2020-NOV-27
 *  Update 2021-OCT-11
 *  https://wwww.aeq-web.com
 * 
 *  Modified by: Guntmar Höche 2023-04-05
 */

require_once(dirname(__DIR__, 4) . '/bootstrap/app.php');
require_once(dirname(__DIR__, 3) . '/Infrastructure/Database/dbGetData.php');
require_once(dirname(__DIR__, 3) . '/Application/myFunctions.func.php');

mds_start_session();
if (empty($_SESSION['userId']) || !myFunctions::isUserAdmin((int)$_SESSION['userId'])) {
    http_response_code(403);
    echo "<div class='alert alert-danger' role='alert'>Access denied.</div>";
    return;
}

$debugRows = dbGetData::getRecentTtnDebugRows(30);
$row_cnt = count($debugRows);

function debugTableCell($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($row_cnt > 0) {
    $show_table = "";

    echo "<div class='debug-table-shell'><table id='ttnvalues' class='table table-sm table-striped table-hover align-middle mb-0' style='" . $show_table . "'>" .
            "<thead><tr>" .
                "<th>" . htmlspecialchars(mds_t('internal.map_timestamp'), ENT_QUOTES, 'UTF-8') . "</th>" .
                "<th>TTN Dev ID</th>" .
                "<th>Counter</th>" .
                "<th>Value1 (Temp &deg;C)</th>" .
                "<th>Value2 (Temp2 &deg;C)</th>" .
                "<th>Value3 (Humidity %)</th>" .
                "<th>Value4 (Battery V)</th>" .
                "<th>Gateway</th>" .
                "<th>RSSI</th>" .
                "<th>SNR</th>" .
                "<th>Channel Index</th>" .
                "<th>Bandwidth</th>" .
                "<th>Spreading Factor</th>" .
            "</tr></thead>" .
        "<tbody>";
        foreach ($debugRows as $mysql_row) {
            if ($mysql_row != null) {
                $dev_name;
                $datetime = $mysql_row["datetime"];
                $dev_name = $mysql_row["dev_id"];
                $dev_counter = $mysql_row["dev_counter"];
                $value1 = $mysql_row["dev_value_1"];
                $value2 = $mysql_row["dev_value_2"];
                $value3 = $mysql_row["dev_value_3"];
                $value4 = $mysql_row["dev_value_4"];
                $gateway = $mysql_row["gtw_id"];
                $rssi = $mysql_row["gtw_rssi"];
                $snr = $mysql_row["gtw_snr"];
                $channel_index = $mysql_row["gtw_channel_index"];
                $bandwidth = $mysql_row["gtw_bandwidth"];
                $spreading_factor = $mysql_row["gtw_sf"];
        
                echo "<tr>";
                echo "<td>" . debugTableCell($datetime) . "</td>";
                echo "<td>" . debugTableCell($dev_name) . "</td>";
                echo "<td>" . debugTableCell($dev_counter) . "</td>";
                echo "<td>" . debugTableCell($value1) . "</td>";
                echo "<td>" . debugTableCell($value2) . "</td>";
                echo "<td>" . debugTableCell($value3) . "</td>";
                echo "<td>" . debugTableCell($value4) . "</td>";
                echo "<td>" . debugTableCell($gateway) . "</td>";
                echo "<td>" . debugTableCell($rssi) . "</td>";
                echo "<td>" . debugTableCell($snr) . "</td>";
                echo "<td>" . debugTableCell($channel_index) . "</td>";
                echo "<td>" . debugTableCell($bandwidth) . "</td>";
                echo "<td>" . debugTableCell($spreading_factor) . "</td>";
                echo "</tr>";
            }
        }
} else {
	echo  "<div class='alert alert-danger' role='alert'>" . htmlspecialchars(mds_t('internal.debug_no_data'), ENT_QUOTES, 'UTF-8') . "</div>";
}
?>
</tbody>
</table>
</div>
