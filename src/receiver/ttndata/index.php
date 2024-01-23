<?php
/*
 *  Version 1.1
 *  Created 2020-NOV-27
 *  Update 2021-OCT-11
 *  https://wwww.aeq-web.com
 * 
 *  Modified by: Guntmar Höche 2023-04-05
 */

require_once(dirname(__FILE__, 3).'/configuration.php');
$config  = new configuration();

$DATABASE_HOST = $config::$dbHost;
$DATABASE_USERNAME = $config::$dbUser;
$DATABASE_PASSWORD = $config::$dbPassword;
$DATABASE_NAME = $config::$dbName;

$db_connect = mysqli_connect($DATABASE_HOST, $DATABASE_USERNAME, $DATABASE_PASSWORD, $DATABASE_NAME);
$sel_data = mysqli_query($db_connect, "SELECT * FROM `ttnDataLoraBoatMonitor` ORDER BY `id` DESC");
$row_cnt = mysqli_num_rows($sel_data);

if ($row_cnt > 0) {
    if ($row_cnt > 0) {
        $show_table = "";
    } else {
        $show_table = "display: none;";
        echo 'Error: No values in database!';
    }

    echo "<table id='ttnvalues' class='table' style=" . $show_table . ">" .
            "<thead><tr>" .
                "<th>Time</th>" .
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
    if ($row_cnt >= 30) {
        $i_max = 30;
    } else {
        $i_max = $row_cnt - 1;
    }
        for ($i=0; $i <= $i_max; $i++) {
            $mysql_row = mysqli_fetch_array($sel_data);
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
                echo "<td>" . $datetime . "</td>";
                echo "<td>" . $dev_name . "</td>";
                echo "<td>" . $dev_counter . "</td>";
                echo "<td>" . $value1 . "</td>";
                echo "<td>" . $value2 . "</td>";
                echo "<td>" . $value3 . "</td>";
                echo "<td>" . $value4 . "</td>";
                echo "<td>" . $gateway . "</td>";
                echo "<td>" . $rssi . "</td>";
                echo "<td>" . $snr . "</td>";
                echo "<td>" . $channel_index . "</td>";
                echo "<td>" . $bandwidth . "</td>";
                echo "<td>" . $spreading_factor . "</td>";
                echo "</tr>";
            }
        }
} else {
	echo  "<div class='alert alert-danger' role='alert'>No Data received.</div>";
}
?>
</tbody>
</table>
