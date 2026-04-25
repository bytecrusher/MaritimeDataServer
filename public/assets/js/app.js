
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
const chartBoardVisibility = {
  temperature: new Map(),
  adc: new Map(),
  other: new Map(),
  events: new Map(),
};

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
  initializeChartBoardFilters();
  initializeEventSummaryChart();
  initializeEventTimeline();
  // TODO: Check, how to add values with timestamp (currently it begins from the left to add values, indepented from the timestampt).

  for (let i in gaugesArrayHelperBig) {
    var randomColor = gaugesArrayHelperBig[i]["ChartColor"];
    var backgroundColor = randomColor;
    var borderColor = randomColor;

    if ( (gaugesArrayHelperBig[i]["typename"] == "DS18B20") || (gaugesArrayHelperBig[i]["NameOfSensors"] == "BME280.Temp") ) {
      addDataToChart(window.myChart, 'temperature', gaugesArrayHelperBig[i]["sensorId"], 200, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor, gaugesArrayHelperBig[i]["BoardName"] + "." + gaugesArrayHelperBig[i]["NameOfSensors"], gaugesArrayHelperBig[i]["channelNr"]-1, gaugesArrayHelperBig[i]["BoardId"], gaugesArrayHelperBig[i]["BoardName"]);
      addLabelsToChart(window.myChart, gaugesArrayHelperBig[i]["sensorId"], 200, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
    }

    if (gaugesArrayHelperBig[i]["typename"] == "ADC") {
      addDataToChart(window.myChart2, 'adc', gaugesArrayHelperBig[i]["sensorId"], 200, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor, gaugesArrayHelperBig[i]["BoardName"] + "." + gaugesArrayHelperBig[i]["NameOfSensors"], gaugesArrayHelperBig[i]["channelNr"]-1, gaugesArrayHelperBig[i]["BoardId"], gaugesArrayHelperBig[i]["BoardName"]);
      addLabelsToChart(window.myChart2, gaugesArrayHelperBig[i]["sensorId"], 200, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
    }

    if ( (gaugesArrayHelperBig[i]["typename"] != "DS18B20") && (gaugesArrayHelperBig[i]["NameOfSensors"] != "BME280.Temp") && (gaugesArrayHelperBig[i]["typename"] != "ADC")) {
      varSensorId = gaugesArrayHelperBig[i]["sensorId"];
      typId = gaugesArrayHelperBig[i]["typId"];
      typename = gaugesArrayHelperBig[i]["typename"];
      sensorname = gaugesArrayHelperBig[i]["BoardName"] + "." + gaugesArrayHelperBig[i]["NameOfSensors"];
      sensorChannel = gaugesArrayHelperBig[i]["channelNr"];

      addDataToChart(window.myChart3, 'other', varSensorId, 200, varSensorId, backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor, sensorname, sensorChannel-1, gaugesArrayHelperBig[i]["BoardId"], gaugesArrayHelperBig[i]["BoardName"]);
      addLabelsToChart(window.myChart3, varSensorId, 200, varSensorId, backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
    }
  }
});

function addDataToChart(destinationChart, chartKey, varSensorId, varMaxValues, varLabel, varBackgroundColor, varBorderColor, varHoverBackgroundColor, varHoverBorderColor, sensorname, sensorChannel, boardId, boardName) {
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
        boardId: String(boardId),
        boardName: boardName,
        chartKey: chartKey,
        hidden: chartBoardVisibility[chartKey].get(String(boardId)) === false,
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

function initializeChartBoardFilters() {
  if (!Array.isArray(gaugesArrayHelperBig)) {
    return;
  }

  const boardsByChart = {
    temperature: new Map(),
    adc: new Map(),
    other: new Map(),
    events: new Map(),
  };

  for (let i in gaugesArrayHelperBig) {
    const boardId = String(gaugesArrayHelperBig[i]["BoardId"]);
    const boardName = gaugesArrayHelperBig[i]["BoardName"];
    const chartKey = getChartKeyForDataset(gaugesArrayHelperBig[i]);

    if (!boardsByChart[chartKey].has(boardId)) {
      boardsByChart[chartKey].set(boardId, boardName);
    }
    if (!chartBoardVisibility[chartKey].has(boardId)) {
      chartBoardVisibility[chartKey].set(boardId, true);
    }
  }

  if (Array.isArray(window.eventChartSensors)) {
    window.eventChartSensors.forEach(function (eventSensor) {
      const boardId = String(eventSensor.boardId);
      boardsByChart.events.set(boardId, eventSensor.boardName);
      if (!chartBoardVisibility.events.has(boardId)) {
        chartBoardVisibility.events.set(boardId, true);
      }
    });
  }

  Object.keys(chartBoardVisibility).forEach(function (chartKey) {
    const filterContainer = document.getElementById('chart-device-filter-' + chartKey);
    if (!filterContainer) {
      return;
    }

    filterContainer.innerHTML = '';
    boardsByChart[chartKey].forEach((boardName, boardId) => {
      const wrapper = document.createElement('div');
      wrapper.className = 'd-inline-flex align-items-center border rounded px-2 py-1 bg-light-subtle';

      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.className = 'form-check-input chart-device-filter-checkbox me-2';
      checkbox.dataset.boardId = boardId;
      checkbox.dataset.chartKey = chartKey;
      checkbox.checked = chartBoardVisibility[chartKey].get(boardId) !== false;
      checkbox.addEventListener('change', function () {
        setChartBoardVisibility(chartKey, boardId, checkbox.checked);
      });

      const text = document.createElement('span');
      text.className = 'small fw-semibold me-2';
      text.textContent = boardName;

      const soloButton = document.createElement('button');
      soloButton.type = 'button';
      soloButton.className = 'btn btn-sm btn-link p-0 text-decoration-none';
      soloButton.dataset.boardId = boardId;
      soloButton.dataset.chartKey = chartKey;
      soloButton.textContent = 'Nur dieses';
      soloButton.addEventListener('click', function () {
        setOnlyChartBoardVisible(chartKey, boardId);
      });

      wrapper.appendChild(checkbox);
      wrapper.appendChild(text);
      wrapper.appendChild(soloButton);
      filterContainer.appendChild(wrapper);
      updateChartBoardFilterButton(chartKey, boardId);
    });
  });

  document.querySelectorAll('.chart-show-all-devices').forEach(function (button) {
    button.addEventListener('click', function () {
      setAllChartBoardVisibility(button.dataset.chartKey, true);
    });
  });

  document.querySelectorAll('.chart-hide-all-devices').forEach(function (button) {
    button.addEventListener('click', function () {
      setAllChartBoardVisibility(button.dataset.chartKey, false);
    });
  });
}

function setAllChartBoardVisibility(chartKey, isVisible) {
  chartBoardVisibility[chartKey].forEach(function (_, boardId) {
    chartBoardVisibility[chartKey].set(boardId, isVisible);
    updateChartBoardDatasets(chartKey, boardId);
    updateChartBoardFilterButton(chartKey, boardId);
  });
}

function setChartBoardVisibility(chartKey, boardId, isVisible) {
  chartBoardVisibility[chartKey].set(boardId, isVisible);
  updateChartBoardDatasets(chartKey, boardId);
  updateChartBoardFilterButton(chartKey, boardId);
}

function setOnlyChartBoardVisible(chartKey, activeBoardId) {
  chartBoardVisibility[chartKey].forEach(function (_, boardId) {
    const isVisible = String(boardId) === String(activeBoardId);
    chartBoardVisibility[chartKey].set(boardId, isVisible);
    updateChartBoardDatasets(chartKey, boardId);
    updateChartBoardFilterButton(chartKey, boardId);
  });
}

function updateChartBoardDatasets(chartKey, boardId) {
  if (chartKey === 'events') {
    updateEventSummaryChart();
    updateEventTimelineVisibility();
    return;
  }
  const chartInstance = getChartInstance(chartKey);
  if (!chartInstance || !chartInstance.data || !Array.isArray(chartInstance.data.datasets)) {
    return;
  }

  chartInstance.data.datasets.forEach(function (dataset) {
    if (String(dataset.boardId) === String(boardId)) {
      dataset.hidden = chartBoardVisibility[chartKey].get(String(boardId)) === false;
    }
  });
  chartInstance.update();
}

function updateChartBoardFilterButton(chartKey, boardId) {
  const checkbox = document.querySelector('.chart-device-filter-checkbox[data-chart-key="' + chartKey + '"][data-board-id="' + boardId + '"]');
  if (!checkbox) {
    return;
  }

  const isVisible = chartBoardVisibility[chartKey].get(String(boardId)) !== false;
  checkbox.checked = isVisible;
}

function getChartInstance(chartKey) {
  if (chartKey === 'temperature') {
    return window.myChart;
  }
  if (chartKey === 'adc') {
    return window.myChart2;
  }
  return window.myChart3;
}

function getChartKeyForDataset(datasetInfo) {
  if ((datasetInfo["typename"] == "DS18B20") || (datasetInfo["NameOfSensors"] == "BME280.Temp")) {
    return 'temperature';
  }
  if (datasetInfo["typename"] == "ADC") {
    return 'adc';
  }
  return 'other';
}

function initializeEventTimeline() {
  const eventTimelineContainer = document.getElementById('event-timeline-container');
  if (!eventTimelineContainer) {
    return;
  }

  if (eventTimelineContainer.dataset.serverRendered === '1') {
    updateEventTimelineVisibility();
    return;
  }

  if (!Array.isArray(window.eventChartSensors) || window.eventChartSensors.length === 0) {
    eventTimelineContainer.innerHTML = '<div class="event-timeline-empty">Noch keine Wakeup- oder Standby-Ereignisse vorhanden.</div>';
    return;
  }
}

function initializeEventSummaryChart() {
  const eventSummaryCanvas = document.getElementById('eventSummaryCanvas');
  if (!eventSummaryCanvas) {
    return;
  }

  const eventSummaryData = Array.isArray(window.eventTimelineSummary) ? window.eventTimelineSummary : [];
  if (eventSummaryData.length === 0) {
    const container = eventSummaryCanvas.parentElement;
    if (container) {
      container.innerHTML = '<div class="event-timeline-empty">Noch keine Wakeup- oder Standby-Ereignisse vorhanden.</div>';
    }
    return;
  }

  window.eventSummaryChart = new Chart(eventSummaryCanvas, {
    type: 'bar',
    data: {
      labels: [],
      datasets: [
        {
          label: 'Wakeup',
          data: [],
          backgroundColor: 'rgba(22, 163, 74, 0.82)',
          borderColor: 'rgba(21, 128, 61, 1)',
          borderWidth: 1,
          borderRadius: 6,
          borderSkipped: false,
        },
        {
          label: 'Standby',
          data: [],
          backgroundColor: 'rgba(245, 158, 11, 0.82)',
          borderColor: 'rgba(217, 119, 6, 1)',
          borderWidth: 1,
          borderRadius: 6,
          borderSkipped: false,
        }
      ]
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      interaction: {
        mode: 'index',
        intersect: false,
      },
      plugins: {
        legend: {
          position: 'top',
          labels: {
            usePointStyle: true,
            boxWidth: 10,
            color: '#334155',
            font: {
              weight: '600',
            }
          }
        },
        tooltip: {
          callbacks: {
            footer: function (tooltipItems) {
              const total = tooltipItems.reduce(function (sum, item) {
                return sum + (Number(item.raw) || 0);
              }, 0);
              return 'Gesamt: ' + total;
            }
          }
        }
      },
      scales: {
        x: {
          stacked: true,
          grid: {
            display: false,
          },
          ticks: {
            color: '#475569',
            font: {
              weight: '600',
            }
          }
        },
        y: {
          stacked: true,
          beginAtZero: true,
          ticks: {
            precision: 0,
            color: '#64748b',
          },
          grid: {
            color: 'rgba(148, 163, 184, 0.18)',
          },
          title: {
            display: true,
            text: 'Anzahl Ereignisse',
            color: '#475569',
          }
        }
      }
    }
  });

  updateEventSummaryChart();
}

function updateEventSummaryChart() {
  if (!window.eventSummaryChart) {
    return;
  }

  const eventSummaryData = Array.isArray(window.eventTimelineSummary) ? window.eventTimelineSummary : [];
  const visibleSummaries = eventSummaryData.filter(function (summaryEntry) {
    return chartBoardVisibility.events.get(String(summaryEntry.boardId)) !== false;
  });

  window.eventSummaryChart.data.labels = visibleSummaries.map(function (summaryEntry) {
    return summaryEntry.boardName;
  });
  window.eventSummaryChart.data.datasets[0].data = visibleSummaries.map(function (summaryEntry) {
    return Number(summaryEntry.wakeupCount || 0);
  });
  window.eventSummaryChart.data.datasets[1].data = visibleSummaries.map(function (summaryEntry) {
    return Number(summaryEntry.standbyCount || 0);
  });
  window.eventSummaryChart.update();
}

function updateEventTimelineVisibility() {
  const eventTimelineContainer = document.getElementById('event-timeline-container');
  if (!eventTimelineContainer) {
    return;
  }

  const boardSections = Array.from(eventTimelineContainer.querySelectorAll('.event-timeline-board[data-event-board-id]'));
  if (boardSections.length === 0) {
    return;
  }

  let hasVisibleBoards = false;
  boardSections.forEach(function (boardSection) {
    const boardId = String(boardSection.dataset.eventBoardId || '');
    const isVisible = chartBoardVisibility.events.get(boardId) !== false;
    boardSection.hidden = !isVisible;
    if (isVisible) {
      hasVisibleBoards = true;
    }
  });

  let emptyState = eventTimelineContainer.querySelector('.event-timeline-empty.is-filter-empty');
  if (!hasVisibleBoards) {
    if (!emptyState) {
      emptyState = document.createElement('div');
      emptyState.className = 'event-timeline-empty is-filter-empty';
      emptyState.textContent = 'Für die aktuell ausgewählten Devices liegen keine ESP-Ereignisse vor.';
      eventTimelineContainer.appendChild(emptyState);
    }
    emptyState.hidden = false;
  } else if (emptyState) {
    emptyState.hidden = true;
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
