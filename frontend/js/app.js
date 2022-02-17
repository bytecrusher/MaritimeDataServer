/**
 * @author      Guntmar Höche
 * @license     TBD
 * @datetime    13 Februar 2022
 * @perpose     Runs if tab "Charts" is selected and collects the last x sensor data and push these data into the canvas object for display in the chart.
 * @input       -
 * @output      -
 */

var ctx = $("#mycanvas");
var barGraph = null;
var varIdent = getCookie("identifier");
var varToken = getCookie("securitytoken");

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

$(async function() {
  $.getJSON( "api/getSensorIds.php", {identifier: varIdent, securitytoken: varToken }, async function( data ) {
    var red = 0;
    var green = 100;
    var blue = 0;
    $.each( data, function( key, val ) {
      var backgroundColor = getRandomColor();
      var borderColor = backgroundColor;
      var hoverBackgroundColor = 'rgba(' + red + ', ' + green + ', ' + blue + ', 1)';
      var hoverBorderColor = 'rgba(' + red + ', ' + green + ', ' + blue + ', 1)';
      addDataToChart(varIdent, varToken, val.id, 200, val.name, backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
      red = red + 200;
      green = green - 100;
      blue = blue + 100;
    });
  });
});

function addDataToChart(varIdent, varToken, varsensorId, varmaxValues, varLabel, varbackgroundColor, varborderColor, varhoverBackgroundColor, varhoverBorderColor) {
  $.getJSON('api/getSensorDataSet.php', { identifier: varIdent, securitytoken: varToken, sensorId:varsensorId, maxValues:varmaxValues}, async function(data, textStatus, jqXHR){
    var localLabel = varLabel;
    //console.log(localLabel + ", " + varsensorId, ", " + data[0].value1);
    console.log("data lenght: " + data.length);
    var id = [];
    var value1 = [];
    var val_time = [];
    for(var i in data) {
      id.push("id " + data[i].id);
      value1.push(data[i].value1);
      val_time.push(data[i].sensor_timestamp);
    }

    var chartdata = {
      labels: val_time,
      datasets : [
        {
          label: localLabel,
          backgroundColor: varbackgroundColor,
          borderColor: varborderColor,
          hoverBackgroundColor: varhoverBackgroundColor,
          hoverBorderColor: varhoverBorderColor,
          data: value1,
          tension: 0.5
        }
      ]
    };
    var ctx = $("#mycanvas");
    if (window.barGraph == null) {
      window.barGraph = new Chart(ctx, {
        type: 'line',
        data: chartdata,
        options: {
          scales: {
            x: {
              display: true,
              offset: true,
              type: 'time',
              time: {
                //unit: 'hour',
                displayFormats: {
                  'millisecond': 'SSS',
                  'second': 'ss:SSS',
                  'minute': 'HH:mm:ss',
                  'hour': 'HH:mm',
                  'day': 'd.M.',
                  'week': 'd.M.',
                  'month': 'M.yyyy',
                  'quarter': 'M.yyyy',
                  'year': 'yyyy',
                }
              },
              ticks: {
                major: {
                  fontStyle: 'bold',
                  fontColor: '#FFFF00'
                }
              }
            }
          }
        },
      });
      window.barGraph.update();
    } else {
      window.barGraph.data.datasets.push({
        label: localLabel,
        backgroundColor: varbackgroundColor,
        borderColor: varborderColor,
        hoverBackgroundColor: varhoverBackgroundColor,
        hoverBorderColor: varhoverBorderColor,
        data: value1
      });
      window.barGraph.update();
    }
  })
  .done(function () {
    //alert('Request done!');
  })
  .fail(function (jqxhr,settings,ex) {
    alert('failed, '+ ex);
    console.log(data);
  });
}

function getRandomColor() {
  var letters = '0123456789ABCDEF'.split('');
  var color = '#';
  for (var i = 0; i < 6; i++ ) {
      color += letters[Math.floor(Math.random() * 16)];
  }
  return color;
}

function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

