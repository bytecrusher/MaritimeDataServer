<!doctype html>
<html lang=en>
<head>
<meta charset=utf-8>
<title>MSD Simulator</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

<link rel="stylesheet" href="./../frontend/css/style.css">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

</head>
<body>
    <?php
    require_once((__DIR__) . '/mdsSimulatorConfig.php');
    $mdsConfig = new mdsSimulatorConfig();
    $mdsDestServer = $mdsConfig::$mdsDestination;
    ?>
<div class="container-xl">
<fieldset> 
<legend><h2>MDS - TTN Simulator</h2></legend>
<fieldset >
	<legend class="float-none mySensorsFieldsetLegend">Destination server</legend>
    <?php
    //$subDir = str_replace($_SERVER['PWD'],"",__DIR__);
    //echo($subDir);
    //echo(getcwd());
    //echo("<br>");
    //$subDir = str_replace(getcwd(),"",__DIR__);
    $subDir = getcwd();
    //echo(__DIR__);
    //echo("<br>");
    echo($subDir);
    echo("<br>");
    //$subDir = str_replace("/simulator","/src",$subDir);
    $subDir = str_replace("/simulator","",$subDir);
    $subDir = str_replace("/var/www/html","",$subDir);
    echo($subDir);

    $domain = $_SERVER['SERVER_ADDR'];
    if (isset($_SERVER['HTTPS']) &&
        ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
        isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
        $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
        $prefix = 'https://';
    }
    else {
        $prefix = 'http://';
    }
    $baseurl = $prefix . $domain . $subDir . "/receiver/ttndata/ttn.php";
    ?>
    <div class="input-group mb-3">
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="url" value="<?php echo($baseurl); ?>" id="checklocal" checked>
            <label class="ms-1" for="checklocal"><?php echo($baseurl); ?></label>
        </div>
    </div>
    <?php
    foreach($mdsDestServer AS $key => $server) {
    ?>
    <div class="input-group mb-3">
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="url" value="<?php echo($server); ?>" id="check<?php echo($key); ?>">
            <label class="ms-1" for="check<?php echo($key); ?>"><?php echo($server); ?></label>
        </div>
    </div>
    <?php
    }
    ?>
    <!--/div-->
</fieldset>

<fieldset>
	<legend class="float-none mySensorsFieldsetLegend">Temp Battery Data Settings</legend>
    <div class="input-group mb-3">
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkTempBatData" value="tempBat_random" id="checkTempBatData1" onclick="$('#inputTempBat').prop('disabled', true);" checked>
            <label class="ms-1" for="checkTempBatData1">Random value</label>
        </div>
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkTempBatData" value="tempBat_value" id="checkTempBatData2" onclick="$('#inputTempBat').prop('disabled', false);">
            <label class="ms-1" for="checkTempBatData2">Defined Value:</label>
        </div>
        <input type="text" class="form-control" id="inputTempBat" placeholder="value" aria-label="inputTempBat" aria-describedby="inputTempBat" disabled="disabled">
    </div>
</fieldset>

<fieldset>
	<legend class="float-none mySensorsFieldsetLegend">BME250 Data Settings</legend>
    <div class="input-group mb-3">
        <div class="input-group-text col-2">
            Pressure
        </div>
        <div class="input-group-text">
	        <input class="form-check-input mt-0" type="radio" name="checkPressureData" value="Pressure_random" id="checkPressureData1" onclick="$('#inputPressure').prop('disabled', true);" checked>
	        <label class="ms-1" for="checkPressureData1">Random value</label>
        </div>
        <div class="input-group-text">
	        <input class="form-check-input mt-0" type="radio" name="checkPressureData" value="Pressure_value" id="checkPressureData2" onclick="$('#inputPressure').prop('disabled', false);">
	        <label class="ms-1" for="checkPressureData2">Pressure Value:</label>
        </div>
        <input type="text" class="form-control" id="inputPressure" placeholder="value" aria-label="inputPressure" aria-describedby="inputPressure" disabled="disabled">
    </div>

    <div class="input-group mb-3">
        <div class="input-group-text col-2">
	        Temperature
        </div>
        <div class="input-group-text">
	        <input class="form-check-input mt-0" type="radio" name="checkTemperatureData" value="Temperature_random" id="checkTemperatureData1" onclick="$('#inputTemperature').prop('disabled', true);" checked>
	        <label class="ms-1" for="checkTemperatureData1">Random value</label>
        </div>
        <div class="input-group-text">
	        <input class="form-check-input mt-0" type="radio" name="checkTemperatureData" value="Temperature_value" id="checkTemperatureData2" onclick="$('#inputTemperature').prop('disabled', false);">
	        <label class="ms-1" for="checkTemperatureData2">Temperature Value:</label>
        </div> 
        <input type="text" class="form-control" id="inputTemperature" placeholder="value" aria-label="inputTemperature" aria-describedby="inputTemperature" disabled="disabled">
    </div>

    <div class="input-group mb-3">
        <div class="input-group-text col-2">
            Dewpoint
        </div>
        <div class="input-group-text">
	        <input class="form-check-input mt-0" type="radio" name="checkDewpointData" value="Dewpoint_random" id="checkDewpointData1" onclick="$('#inputDewpoint').prop('disabled', true);" checked>
	        <label for="checkDewpointData1">Random value</label>
        </div>
        <div class="input-group-text">
	        <input class="form-check-input mt-0" type="radio" name="checkDewpointData" value="Dewpoint_value" id="checkDewpointData2" onclick="$('#inputDewpoint').prop('disabled', false);">
	        <label for="checkDewpointData2">Dewpoint Value:
        </div>
        <input type="text" class="form-control" id="inputDewpoint" placeholder="value" aria-label="inputDewpoint" aria-describedby="inputDewpoint" disabled="disabled"></label>
    </div>

    <div class="input-group mb-3">
        <div class="input-group-text col-2">
            Humidity
        </div>
        <div class="input-group-text">
	        <input class="form-check-input mt-0" type="radio" name="checkHumidityData" value="Humidity_random" id="checkHumidityData1" onclick="$('#inputHumidity').prop('disabled', true);" checked>
	        <label class="ms-1" for="checkHumidityData1">Random value</label>
        </div>
        <div class="input-group-text"> 
	        <input class="form-check-input mt-0" type="radio" name="checkHumidityData" value="Humidity_value" id="checkHumidityData2" onclick="$('#inputHumidity').prop('disabled', false);">
	        <label class="ms-1" for="checkHumidityData2">Humidity Value:
        </div>
        <input type="text" class="form-control" id="inputHumidity" placeholder="value" aria-label="inputHumidity" aria-describedby="inputHumidity" disabled="disabled"></label>
    </div>
</fieldset>

<fieldset>
    <legend class="float-none mySensorsFieldsetLegend">ADC Settings</legend>
    <div class="input-group mb-3">
        <div class="input-group-text col-2">
            Channel 1
        </div>
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkAdcCh1Data" value="AdcCh1_random" id="checkAdcCh1Data1" onclick="$('#inputAdcCh1').prop('disabled', true);" checked>
            <label class="ms-1" for="checkAdcCh1Data1">Random value</label>
        </div>
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkAdcCh1Data" value="AdcCh1_value" id="checkAdcCh1Data2" onclick="$('#inputAdcCh1').prop('disabled', false);">
            <label class="ms-1" for="checkAdcCh1Data2">ADC Channel 1 Value:</label>
        </div>
        <input type="text" class="form-control" id="inputAdcCh1" placeholder="value" aria-label="inputAdcCh1" aria-describedby="inputAdcCh1" disabled="disabled">
    </div>

    <div class="input-group mb-3">
        <div class="input-group-text col-2">
            Channel 2:
        </div>
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkAdcCh2Data" value="AdcCh2_random" id="checkAdcCh2Data1" onclick="$('#inputAdcCh2').prop('disabled', true);" checked>
            <label class="ms-1" for="checkAdcCh2Data1">Random value</label>
        </div>
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkAdcCh2Data" value="AdcCh2_value" id="checkAdcCh2Data2" onclick="$('#inputAdcCh2').prop('disabled', false);">
            <label class="ms-1" for="checkAdcCh2Data2">ADC Channel 2 Value:</label>
        </div>
        <input type="text" class="form-control" id="inputAdcCh2" placeholder="value" aria-label="inputAdcCh2" aria-describedby="inputAdcCh2" disabled="disabled">
    </div>

    <div class="input-group mb-3">
        <div class="input-group-text col-2">
            Channel 3:
        </div>
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkAdcCh3Data" value="AdcCh3_random" id="checkAdcCh3Data1" onclick="$('#inputAdcCh3').prop('disabled', true);" checked>
            <label class="ms-1" for="checkAdcCh3Data1">Random value</label>
        </div>
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkAdcCh3Data" value="AdcCh3_value" id="checkAdcCh3Data2" onclick="$('#inputAdcCh3').prop('disabled', false);">
            <label class="ms-1" for="checkAdcCh3Data2">ADC Channel 3 Value:</label>
        </div>
        <input type="text" class="form-control" id="inputAdcCh3" placeholder="value" aria-label="inputAdcCh3" aria-describedby="inputAdcCh3" disabled="disabled">
    </div>

    <div class="input-group mb-3">
        <div class="input-group-text col-2">
            Channel 4:
        </div>
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkAdcCh4Data" value="AdcCh4_random" id="checkAdcCh4Data1" onclick="$('#inputAdcCh4').prop('disabled', true);" checked>
            <label class="ms-1" for="checkAdcCh4Data1">Random value</label>
        </div>
        <div class="input-group-text">
            <input class="form-check-input mt-0" type="radio" name="checkAdcCh4Data" value="AdcCh4_value" id="checkAdcCh4Data2" onclick="$('#inputAdcCh4').prop('disabled', false);">
            <label class="ms-1" for="checkAdcCh4Data2">ADC Channel 4 Value:</label>
        </div>       
        <input type="text" class="form-control" id="inputAdcCh4" placeholder="value" aria-label="inputAdcCh4" aria-describedby="inputAdcCh4" disabled="disabled">
    </div>
</fieldset>

<fieldset>
	<legend class="float-none mySensorsFieldsetLegend">Send ttn data to server</legend>
    <button class="btn btn-primary" onclick="sendttn()">Single package</button>
    <div style="float:inline-end">
    <button class="btn btn-primary" onclick="sendttninterval()" id="btnsendttninterval">Start interval</button>
    <label for="intervaltimer">interval timer (s)<input id="intervaltimer" type="text" value="10" size="3"></label>
    <button class="btn btn-primary" onclick="stopintervaltimer()" id="btnstopintervaltimer" disabled>Stop interval</button>
    </div>
    <p class="broken"></p>
</fieldset>

<fieldset>
	<legend class="float-none mySensorsFieldsetLegend">Log</legend>
    <div id="txtLog">
    </div>
</fieldset>
</div>


<script>
    ttncounter = 0;
    let timerVariable;

    function sendttninterval() {
        timerVariable = window.setInterval(sendttn, (document.querySelector('input[id="intervaltimer"]').value * 1000));
        document.querySelector('button[id="btnsendttninterval"]').disabled = true
        document.querySelector('button[id="btnstopintervaltimer"]').disabled = false
    }

    function stopintervaltimer() {
        window.clearInterval(timerVariable)
        document.querySelector('button[id="btnsendttninterval"]').disabled = false
        document.querySelector('button[id="btnstopintervaltimer"]').disabled = true
    }

    function sendttn() {
        let gpsdata = randGPS();
        if (document.querySelector('input[name="checkTempBatData"]:checked').value == "tempBat_random") {
            tempbattery = Math.floor(Math.random() * 50) + 1;
        } else {
            tempbattery = document.querySelector('input[id="inputTempBat"]').value
        }

        if (document.querySelector('input[name="checkPressureData"]:checked').value == "Pressure_random") {
            pressure = Math.floor(Math.random() * 2000) + 1000;
        } else {
            pressure = document.querySelector('input[id="inputPressure"]').value
        }

        if (document.querySelector('input[name="checkTemperatureData"]:checked').value == "Temperature_random") {
            temperature = Math.floor(Math.random() * 30) + 1;
        } else {
            temperature = document.querySelector('input[id="inputTemperature"]').value
        }

        if (document.querySelector('input[name="checkDewpointData"]:checked').value == "Dewpoint_random") {
            dewpoint = Math.floor(Math.random() * 30) + 1;
        } else {
            dewpoint = document.querySelector('input[id="inputDewpoint"]').value
        }

        if (document.querySelector('input[name="checkHumidityData"]:checked').value == "Humidity_random") {
            humidity = Math.floor(Math.random() * 100) + 1;
        } else {
            humidity = document.querySelector('input[id="inputHumidity"]').value
        }

        if (document.querySelector('input[name="checkAdcCh1Data"]:checked').value == "AdcCh1_random") {
            AdcCh1 = (Math.random() * (15.0 - 8.10)+ 8.10).toFixed(2)
        } else {
            AdcCh1 = document.querySelector('input[id="inputAdcCh1"]').value
        }

        if (document.querySelector('input[name="checkAdcCh2Data"]:checked').value == "AdcCh2_random") {
            AdcCh2 = (Math.random() * (3.3 - 0.10)).toFixed(2)
        } else {
            AdcCh2 = document.querySelector('input[id="inputAdcCh2"]').value
        }

        if (document.querySelector('input[name="checkAdcCh3Data"]:checked').value == "AdcCh3_random") {
            AdcCh3 = (Math.random() * (3.3 - 0.10)).toFixed(2)
        } else {
            AdcCh3 = document.querySelector('input[id="inputAdcCh3"]').value
        }

        if (document.querySelector('input[name="checkAdcCh4Data"]:checked').value == "AdcCh4_random") {
            AdcCh4 = (Math.random() * (3.3 - 0.10)).toFixed(2)
        } else {
            AdcCh4 = document.querySelector('input[id="inputAdcCh4"]').value
        }

        $.ajax({
            method: "POST",
            url: "testttn.php",
            data: {
                url: document.querySelector('input[name="url"]:checked').value, 
                ttncounter: ttncounter,
                tempbattery: tempbattery,
                pressure: pressure,
                temperature: temperature,
                dewpoint: dewpoint, 
                humidity: humidity,
                latitude: gpsdata['lat'],
                longitude: gpsdata['long'],
                voltage: AdcCh1,
                voltage2: AdcCh2,
                level1: AdcCh3,
                level2: AdcCh4
            }
        })
            .done(function( response ) {
            $("p.broken").html(response);
        });
        $('#txtLog').append("Data: ");
        $('#txtLog').append("ttncounter: " + ttncounter + ", ");
        $('#txtLog').append("tempbattery: " + tempbattery + ", ");
        $('#txtLog').append("pressure: " + pressure + ", ");
        $('#txtLog').append("temperature: " + temperature + ", ");
        $('#txtLog').append("dewpoint: " + dewpoint + ", ");
        $('#txtLog').append("humidity: " + humidity + ", ");
        $('#txtLog').append("randGPS: " + gpsdata['lat'] + ", " + gpsdata['long'] + ", ");
        $('#txtLog').append("voltage: " + AdcCh1 + ", voltage2: " + AdcCh2 + ", level1: " + AdcCh3 + ", level2: " + AdcCh4 + "<br>");
        ttncounter++;
    }

    function randGPS(lat = 53.017585, long = 8.885182, radius = 50000) {
        let r = radius / 111300; // = 100 meters
        let y0 = lat;
        let x0 = long;
        let u = Math.random();
        let v = Math.random();
        let w = r * Math.sqrt(u);
        let t = 2 * Math.PI * v;
        let x = w * Math.cos(t);
        let y1 = w * Math.sin(t);
        let x1 = x / Math.cos(y0);

        let newY = y0 + y1;
        let newX = x0 + x1;
        return { lat: newY, long: newX };
    }
</script>
</body>
</html>