
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
const eventTimelineState = {
  boardEvents: new Map(),
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
    renderEventTimeline();
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

  if (!Array.isArray(window.eventChartSensors) || window.eventChartSensors.length === 0) {
    eventTimelineContainer.innerHTML = '<div class="event-timeline-empty">Noch keine Wakeup- oder Standby-Ereignisse vorhanden.</div>';
    return;
  }

  window.eventChartSensors.forEach(function (eventSensor) {
    $.getJSON('api/getSensorDataSet.php', { sensorId: eventSensor.sensorId, maxValues: 200 }, function (data) {
      const boardId = String(eventSensor.boardId);
      const currentEvents = eventTimelineState.boardEvents.get(boardId) || [];
      const extractedEvents = extractBoardEventsFromSensorRows(data, eventSensor);
      eventTimelineState.boardEvents.set(boardId, currentEvents.concat(extractedEvents));
      renderEventTimeline();
    }).fail(function (jqxhr, settings, ex) {
      console.log('failed (initializeEventTimeline), ' + eventSensor.sensorId + ', ' + ex);
    });
  });
}

function extractBoardEventsFromSensorRows(rows, eventSensor) {
  if (!Array.isArray(rows)) {
    return [];
  }

  const extractedEvents = [];
  rows.forEach(function (row) {
    appendEventFromPair(extractedEvents, row.value1, row.value2, row, eventSensor, 1);
    appendEventFromPair(extractedEvents, row.value3, row.value4, row, eventSensor, 3);

    if (extractedEvents.length === 0 && isMeaningfulEventLabel(row.value1)) {
      extractedEvents.push(buildBoardEventEntry(row.value1, row.val_date + ' ' + row.val_time, row, eventSensor, 1));
    }
  });

  return extractedEvents;
}

function appendEventFromPair(targetEvents, labelValue, timestampValue, row, eventSensor, sourceIndex) {
  if (!isMeaningfulEventLabel(labelValue)) {
    return;
  }

  const eventTimestamp = isLikelyEventTimestamp(timestampValue)
    ? String(timestampValue).trim()
    : ((row.val_date || '') + ' ' + (row.val_time || '')).trim();

  targetEvents.push(buildBoardEventEntry(labelValue, eventTimestamp, row, eventSensor, sourceIndex));
}

function buildBoardEventEntry(labelValue, timestampValue, row, eventSensor, sourceIndex) {
  const normalizedLabel = normalizeEventLabel(labelValue);
  return {
    boardId: String(eventSensor.boardId),
    boardName: eventSensor.boardName,
    sensorId: String(eventSensor.sensorId),
    sensorName: eventSensor.sensorName,
    label: normalizedLabel.label,
    stateClass: normalizedLabel.stateClass,
    rawLabel: labelValue,
    timestamp: timestampValue,
    rowTimestamp: ((row.val_date || '') + ' ' + (row.val_time || '')).trim(),
    readingTime: row.reading_time || null,
    sourceIndex: sourceIndex,
  };
}

function normalizeEventLabel(labelValue) {
  const rawLabel = String(labelValue || '').trim();
  const normalized = rawLabel.toLowerCase();

  if (normalized.includes('sleep') || normalized.includes('standby')) {
    return { label: 'Standby', stateClass: 'is-standby' };
  }
  if (normalized.includes('wake')) {
    return { label: 'Wakeup', stateClass: 'is-wakeup' };
  }

  return { label: rawLabel, stateClass: 'is-other' };
}

function isMeaningfulEventLabel(labelValue) {
  if (labelValue === null || labelValue === undefined) {
    return false;
  }

  const normalized = String(labelValue).trim();
  if (normalized === '') {
    return false;
  }

  return Number.isNaN(Number(normalized));
}

function isLikelyEventTimestamp(timestampValue) {
  if (timestampValue === null || timestampValue === undefined) {
    return false;
  }

  return /^\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2}:\d{2}$/.test(String(timestampValue).trim());
}

function renderEventTimeline() {
  const eventTimelineContainer = document.getElementById('event-timeline-container');
  if (!eventTimelineContainer) {
    return;
  }

  const visibleBoards = [];
  chartBoardVisibility.events.forEach(function (isVisible, boardId) {
    if (isVisible !== false) {
      visibleBoards.push(String(boardId));
    }
  });

  const boardEntries = Array.from(eventTimelineState.boardEvents.entries())
    .filter(function ([boardId]) {
      return visibleBoards.includes(String(boardId));
    })
    .map(function ([boardId, boardEvents]) {
      const sortedEvents = boardEvents.slice().sort(function (a, b) {
        const left = Date.parse((a.readingTime || '').replace(' ', 'T')) || 0;
        const right = Date.parse((b.readingTime || '').replace(' ', 'T')) || 0;
        return right - left;
      });
      return {
        boardId: boardId,
        boardName: sortedEvents[0] ? sortedEvents[0].boardName : boardId,
        events: sortedEvents.slice(0, 40),
      };
    });

  if (boardEntries.length === 0) {
    eventTimelineContainer.innerHTML = '<div class="event-timeline-empty">Für die aktuell ausgewählten Devices liegen keine ESP-Ereignisse vor.</div>';
    return;
  }

  eventTimelineContainer.innerHTML = boardEntries.map(function (boardEntry) {
    const items = boardEntry.events.map(function (eventEntry) {
      const detailBits = [];
      if (eventEntry.sensorName) {
        detailBits.push(eventEntry.sensorName);
      }
      if (eventEntry.rawLabel && eventEntry.rawLabel !== eventEntry.label) {
        detailBits.push('Rohwert: ' + eventEntry.rawLabel);
      }
      if (eventEntry.rowTimestamp && eventEntry.timestamp !== eventEntry.rowTimestamp) {
        detailBits.push('Datensatz: ' + eventEntry.rowTimestamp);
      }
      return '<li class="event-timeline-item">' +
        '<span class="event-timeline-dot ' + eventEntry.stateClass + '"></span>' +
        '<div class="event-timeline-content">' +
          '<div class="event-timeline-title"><strong>' + escapeHtml(eventEntry.label) + '</strong><time>' + escapeHtml(eventEntry.timestamp || eventEntry.rowTimestamp || '-') + '</time></div>' +
          '<div class="event-timeline-meta">' + escapeHtml(detailBits.join(' · ')) + '</div>' +
        '</div>' +
      '</li>';
    }).join('');

    return '<section class="event-timeline-board">' +
      '<div class="event-timeline-head">' +
        '<div><strong>' + escapeHtml(boardEntry.boardName) + '</strong><br><span>' + boardEntry.events.length + ' Ereignisse im Verlauf</span></div>' +
      '</div>' +
      '<ol class="event-timeline-list">' + items + '</ol>' +
    '</section>';
  }).join('');
}

function escapeHtml(value) {
  return String(value || '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
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
