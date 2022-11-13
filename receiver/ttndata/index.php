<?php
/*
 *  Version 1.1
 *  Created 2020-NOV-27
 *  Update 2021-OCT-11
 *  https://wwww.aeq-web.com
 */

require_once(dirname(__FILE__, 3).'/configuration.php');
$config  = new configuration();

$DATABASE_HOST = $config::$db_host;
$DATABASE_USERNAME = $config::$db_user;
$DATABASE_PASSWORD = $config::$db_password;
$DATABASE_NAME = $config::$db_name;

//$db_connect = mysqli_connect($DATABASE_HOST, $DATABASE_USERNAME, $DATABASE_PASSWORD, $DATABASE_NAME) or die(mysql_error());
$db_connect = mysqli_connect($DATABASE_HOST, $DATABASE_USERNAME, $DATABASE_PASSWORD, $DATABASE_NAME);
$sel_data = mysqli_query($db_connect, "SELECT * FROM `ttnDataLoraBoatMonitor` ORDER BY `id` DESC");

$mysql_row = mysqli_fetch_array($sel_data);
$row_cnt = mysqli_num_rows($sel_data);

$dev_name = $mysql_row["dev_id"];
$datetime = $mysql_row["datetime"];
$gateway = $mysql_row["gtw_id"];
$rssi = $mysql_row["gtw_rssi"];
$temperature = $mysql_row["dev_value_1"];
$temperature2 = $mysql_row["dev_value_2"];
$humidity = $mysql_row["dev_value_3"];
$battery = $mysql_row["dev_value_4"];

if ($row_cnt > 0) {
    $show_table = "";
} else {
    $show_table = "display: none;";
    echo 'Error: No values in database!';
}
?>
        <table id="ttnvalues" class="table" style="<?php echo $show_table; ?>">
            <tr>
                <th><?php echo $dev_name; ?></th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Time</td>
                <td><?php echo $datetime; ?></td>
            </tr>
            <tr>
                <td>Temperature</td>
                <td><?php echo $temperature; ?> &deg;C</td>
            </tr>
            <tr>
                <td>Temperature2</td>
                <td><?php echo $temperature2; ?> &deg;C</td>
            </tr>
            <tr>
                <td>Humidity</td>
                <td><?php echo $humidity; ?> %</td>
            </tr>
            <tr>
                <td>Battery</td>
                <td><?php echo $battery; ?> V</td>
            </tr>
            <tr>
                <td>Gateway</td>
                <td><?php echo $gateway; ?></td>
            </tr>
            <tr>
                <td>RSSI</td>
                <td><?php echo $rssi; ?></td>
            </tr>
        </table>

