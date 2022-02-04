
/**
 *
 * @author: Guntmar Höche
 * @license: TBD
 */

// Now we can create a new instance of our chart, using the Chart.js API
var ctx = $("#mycanvas");
var barGraph = null;

function sleep(ms) {
  return new Promise(resolve => setTimeout(resolve, ms));
}

// Chart view. Runs on click on "temperature" tab, and collects data to show
$(document).ready(async function(){
  $('#hreftemperatures').click(async function (e) {
    var backgroundColor = 'rgba(0, 255, 0, 0.75)';
    var borderColor = 'rgba(0, 255, 0, 0.75)';
    var hoverBackgroundColor = 'rgba(0, 100, 0, 1)';
    var hoverBorderColor = 'rgba(0, 100, 0, 1)';

    addDataToChart(8, 200, "Outside", backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);

    var backgroundColor = 'rgba(0, 0, 255, 0.75)';
    var borderColor = 'rgba(0, 0, 255, 0.75)';
    var hoverBackgroundColor = 'rgba(0, 0, 100, 1)';
    var hoverBorderColor = 'rgba(0, 0, 100, 1)';
    addDataToChart(7, 200, "Inside", backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
  });
});

function addDataToChart(varsensorId, varmaxValues, varLabel, varbackgroundColor, varborderColor, varhoverBackgroundColor, varhoverBorderColor) {
  $.getJSON('api/getSensorDataSet.php', { sensorId:varsensorId, maxValues:varmaxValues}, async function(data, textStatus, jqXHR){
    var localLabel = varLabel;
    console.log(localLabel);
    var id = [];
    var value1 = [];
    var val_time = [];
    for(var i in data) {
      id.push("id " + data[i].id);
      value1.push(data[i].value1);
      val_time.push(data[i].val_time);
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
          data: value1
        }
      ]
    };
    var ctx = $("#mycanvas");
    if (window.barGraph == null) {
      window.barGraph = new Chart(ctx, {
        type: 'line',
        data: chartdata,
      });
    } else {
      window.barGraph.data.labels.push(localLabel);
      window.barGraph.data.datasets.push({
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

