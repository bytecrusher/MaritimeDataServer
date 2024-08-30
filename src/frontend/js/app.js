
/**
 *
 * @author: Guntmar Höche
 * @license: TBD
 * TODO: for every Board its own canvas.
 * TODO: settings options for canvas to define, witch channels will be display.
 */

const myChart = null;
const myChart2 = null;
const myChart3 = null;

//function sleep(ms) {
//  return new Promise(resolve => setTimeout(resolve, ms));
//}

// Chart view. Runs on click on "Charts" tab, and collects data to show
$(document).ready(async function(){
  var backgroundColor = null;
  var borderColor = null;
  var hoverBackgroundColor = 'rgba(0, 100, 0, 1)';
  var hoverBorderColor = 'rgba(0, 100, 0, 1)';
  var varSensorId = null;
  InitialSetupChart();
  // TODO: Check, how to add values with timestamp (currently it begins from the left to add values, indepented from the timestampt).

  for (let i in gaugesArrayHelperBig) {
    var randomColor = gaugesArrayHelperBig[i]["ChartColor"];
    var backgroundColor = randomColor;
    var borderColor = randomColor;

    if ( (gaugesArrayHelperBig[i]["typename"] == "DS18B20") || (gaugesArrayHelperBig[i]["NameOfSensors"] == "BME280.Temp") ) {
      addDataToChart(window.myChart, gaugesArrayHelperBig[i]["sensorId"], 200, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor, gaugesArrayHelperBig[i]["BoardName"] + "." + gaugesArrayHelperBig[i]["NameOfSensors"], gaugesArrayHelperBig[i]["channelNr"]-1);
      addLabelsToChart(window.myChart, gaugesArrayHelperBig[i]["sensorId"], 200, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
    }

    if (gaugesArrayHelperBig[i]["typename"] == "ADC") {
      addDataToChart(window.myChart2, gaugesArrayHelperBig[i]["sensorId"], 200, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor, gaugesArrayHelperBig[i]["BoardName"] + "." + gaugesArrayHelperBig[i]["NameOfSensors"], gaugesArrayHelperBig[i]["channelNr"]-1);
      addLabelsToChart(window.myChart2, gaugesArrayHelperBig[i]["sensorId"], 200, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
    }

    if ( (gaugesArrayHelperBig[i]["typename"] != "DS18B20") && (gaugesArrayHelperBig[i]["NameOfSensors"] != "BME280.Temp") && (gaugesArrayHelperBig[i]["typename"] != "ADC")) {
      varSensorId = gaugesArrayHelperBig[i]["sensorId"];
      typId = gaugesArrayHelperBig[i]["typId"];
      typename = gaugesArrayHelperBig[i]["typename"];
      sensorname = gaugesArrayHelperBig[i]["BoardName"] + "." + gaugesArrayHelperBig[i]["NameOfSensors"];
      sensorChannel = gaugesArrayHelperBig[i]["channelNr"];

      addDataToChart(window.myChart3, varSensorId, 200, varSensorId, backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor, sensorname, sensorChannel-1);
      addLabelsToChart(window.myChart3, varSensorId, 200, varSensorId, backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
    }
  }
});

function addDataToChart(destinationChart, varSensorId, varMaxValues, varLabel, varBackgroundColor, varBorderColor, varHoverBackgroundColor, varHoverBorderColor, sensorname, sensorChannel) {
  if (varSensorId != null) {
    // TODO: make more efficient: call get function with the channel name and receive only these channels
    $.getJSON('api/getSensorDataSet.php', { sensorId:varSensorId, maxValues:varMaxValues}, async function(data, textStatus, jqXHR){
      var id = [];
      var value1 = [];
      for(var i in data) {
        id.push("id " + data[i].id);
        if (sensorChannel == 0) {
          //value1.push(data[i].value1);
          if (data[i].value1 !== undefined && data[i].value1 !== null && data[i].value1 !== "") {
            value1.push(data[i].value1);
          }
        } else if (sensorChannel == 1) {
          if (data[i].value2 !== undefined && data[i].value2 !== null && data[i].value2 !== "") {
            value1.push(data[i].value2);
          }
        } else if (sensorChannel == 2) {
          //value1.push(data[i].value3);
          if (data[i].value3 !== undefined && data[i].value3 !== null && data[i].value3 !== "") {
            value1.push(data[i].value3);
          }
        } else if (sensorChannel == 3) {
          //value1.push(data[i].value4);
          if (data[i].value4 !== undefined && data[i].value4 !== null && data[i].value4 !== "") {
            value1.push(data[i].value4);
          }
        }
      }

      const data1 = window.myChart.data;
      const data2 = window.myChart2.data;
      const data3 = window.myChart3.data;
      const dsColor = varBackgroundColor;
      const newDataset = {
        label: sensorname,
        backgroundColor: dsColor,
        borderColor: dsColor,
        data: value1,
      };
      destinationChart.data.datasets.push(newDataset);
      destinationChart.update();
    })
    .done(function () {
      //alert('Request done!');
    })
    .fail(function (jqxhr,settings,ex) {
      //alert('failed (addDataToChart), ' + varSensorId + ", " + ex);
      console.log('failed (addDataToChart), ' + varSensorId + ", for: " + varLabel + ", " + ex);
      //console.log(data);
    });
  }
}

function addLabelsToChart(destinationChart, varSensorId, varMaxValues, varLabel, varBackgroundColor, varBorderColor, varHoverBackgroundColor, varHoverBorderColor) {
  if (varSensorId != null) {
    $.getJSON('api/getSensorDataSet.php', { sensorId:varSensorId, maxValues:varMaxValues}, async function(data, textStatus, jqXHR){
      var val_time = [];
      for(var i in data) {
        val_time.push(data[i].val_date + " " + data[i].val_time);
      }
      destinationChart.data.labels = val_time;
      destinationChart.update();
    })
    .done(function () {
    })
    .fail(function (jqxhr,settings,ex) {
      alert('failed (addLabelsToChart), '+ ex);
    });
  }
}


function InitialSetupChart(varSensorId, varmaxValues, varLabel, varBackgroundColor, varBorderColor, varHoverBackgroundColor, varHoverBorderColor) {
  const ctx = document.getElementById('mycanvas');
  window.myChart = new Chart(ctx, {
    type: 'line',
    data: {
        /*labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
        datasets: [{
            label: '# of Votes',
            data: [12, 19, 3, 5, 2, 3],
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]*/
    },
    /*options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }*/
  });

  const ctx2 = document.getElementById('mycanvas2');
  window.myChart2 = new Chart(ctx2, {
    type: 'line',
    data: {
        /*labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
        datasets: [{
            label: '# of Votes',
            data: [12, 19, 3, 5, 2, 3],
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]*/
    },
    /*options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }*/
  });

  const ctx3 = document.getElementById('mycanvas3');
  window.myChart3 = new Chart(ctx3, {
    type: 'line',
    data: {
        /*labels: ['Red', 'Blue', 'Yellow', 'Green', 'Purple', 'Orange'],
        datasets: [{
            label: '# of Votes',
            data: [12, 19, 3, 5, 2, 3],
            backgroundColor: [
                'rgba(255, 99, 132, 0.2)',
                'rgba(54, 162, 235, 0.2)',
                'rgba(255, 206, 86, 0.2)',
                'rgba(75, 192, 192, 0.2)',
                'rgba(153, 102, 255, 0.2)',
                'rgba(255, 159, 64, 0.2)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]*/
    },
    /*options: {
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }*/
  });
}
