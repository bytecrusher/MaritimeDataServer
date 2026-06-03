
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

function mdsLabel(key, fallback) {
  return (window.mdsI18n && window.mdsI18n[key]) ? window.mdsI18n[key] : fallback;
}

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
  const defaultChartMaxValues = getDefaultChartMaxValues();
  // TODO: Check, how to add values with timestamp (currently it begins from the left to add values, indepented from the timestampt).

  for (let i in gaugesArrayHelperBig) {
    var randomColor = gaugesArrayHelperBig[i]["ChartColor"];
    var backgroundColor = randomColor;
    var borderColor = randomColor;

    if ( (gaugesArrayHelperBig[i]["typename"] == "DS18B20") || (gaugesArrayHelperBig[i]["NameOfSensors"] == "BME280.Temp") ) {
      addDataToChart(window.myChart, 'temperature', gaugesArrayHelperBig[i]["sensorId"], defaultChartMaxValues, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor, gaugesArrayHelperBig[i]["BoardName"] + "." + gaugesArrayHelperBig[i]["NameOfSensors"], gaugesArrayHelperBig[i]["channelNr"]-1, gaugesArrayHelperBig[i]["BoardId"], gaugesArrayHelperBig[i]["BoardName"]);
      addLabelsToChart(window.myChart, gaugesArrayHelperBig[i]["sensorId"], defaultChartMaxValues, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
    }

    if (gaugesArrayHelperBig[i]["typename"] == "ADC") {
      addDataToChart(window.myChart2, 'adc', gaugesArrayHelperBig[i]["sensorId"], defaultChartMaxValues, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor, gaugesArrayHelperBig[i]["BoardName"] + "." + gaugesArrayHelperBig[i]["NameOfSensors"], gaugesArrayHelperBig[i]["channelNr"]-1, gaugesArrayHelperBig[i]["BoardId"], gaugesArrayHelperBig[i]["BoardName"]);
      addLabelsToChart(window.myChart2, gaugesArrayHelperBig[i]["sensorId"], defaultChartMaxValues, gaugesArrayHelperBig[i]["sensorId"], backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
    }

    if ( (gaugesArrayHelperBig[i]["typename"] != "DS18B20") && (gaugesArrayHelperBig[i]["NameOfSensors"] != "BME280.Temp") && (gaugesArrayHelperBig[i]["typename"] != "ADC")) {
      varSensorId = gaugesArrayHelperBig[i]["sensorId"];
      typId = gaugesArrayHelperBig[i]["typId"];
      typename = gaugesArrayHelperBig[i]["typename"];
      sensorname = gaugesArrayHelperBig[i]["BoardName"] + "." + gaugesArrayHelperBig[i]["NameOfSensors"];
      sensorChannel = gaugesArrayHelperBig[i]["channelNr"];

      addDataToChart(window.myChart3, 'other', varSensorId, defaultChartMaxValues, varSensorId, backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor, sensorname, sensorChannel-1, gaugesArrayHelperBig[i]["BoardId"], gaugesArrayHelperBig[i]["BoardName"]);
      addLabelsToChart(window.myChart3, varSensorId, defaultChartMaxValues, varSensorId, backgroundColor, borderColor, hoverBackgroundColor, hoverBorderColor);
    }
  }
});

function getDefaultChartMaxValues() {
  const preferredChartWindowDays = Number.parseInt(window.preferredChartWindowDays || '7', 10);
  if (preferredChartWindowDays <= 1) {
    return 50;
  }
  if (preferredChartWindowDays <= 7) {
    return 200;
  }
  if (preferredChartWindowDays <= 14) {
    return 400;
  }
  return 800;
}

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
      soloButton.textContent = mdsLabel('onlyThis', 'Only this');
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
    updateEventTimeline24hChart();
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
    eventTimelineContainer.innerHTML = '<div class="event-timeline-empty">' + mdsLabel('noEvents', 'No wakeup or standby events available yet.') + '</div>';
    return;
  }
}

function initializeEventSummaryChart() {
  const eventSummaryCanvas = document.getElementById('eventSummaryCanvas');
  if (!eventSummaryCanvas) {
    return;
  }

  const eventSummaryShell = eventSummaryCanvas.closest('.event-summary-chart-shell');
  if (eventSummaryShell) {
    eventSummaryShell.style.height = '320px';
  }

  const eventSummaryData = Array.isArray(window.eventTimelineSummary) ? window.eventTimelineSummary : [];
  const eventSummaryLabels = Array.isArray(window.eventTimelineSummaryLabels) ? window.eventTimelineSummaryLabels : [];
  if (eventSummaryData.length === 0) {
    const container = eventSummaryCanvas.parentElement;
    if (container) {
      container.innerHTML = '<div class="event-timeline-empty">' + mdsLabel('noEvents', 'No wakeup or standby events available yet.') + '</div>';
    }
    return;
  }

  window.eventSummaryChart = new Chart(eventSummaryCanvas, {
    type: 'bar',
    data: {
      labels: eventSummaryLabels,
      datasets: []
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
              return 'Summe an diesem Tag: ' + total.toFixed(2) + ' h';
            }
          }
        }
      },
      scales: {
        x: {
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
          beginAtZero: true,
          ticks: {
            color: '#64748b',
            callback: function (value) {
              return Number(value).toFixed(1) + ' h';
            }
          },
          grid: {
            color: 'rgba(148, 163, 184, 0.18)',
          },
          title: {
            display: true,
            text: mdsLabel('hoursPerDay', 'Hours per day'),
            color: '#475569',
          }
        }
      }
    }
  });

  updateEventSummaryChart();
}

function initializeEventTimeline24hChart() {
  const eventTimelineCanvas = document.getElementById('eventTimeline24hCanvas');
  if (!eventTimelineCanvas) {
    return;
  }

  const eventTimelineShell = eventTimelineCanvas.closest('.event-summary-chart-shell');
  if (eventTimelineShell) {
    eventTimelineShell.style.height = '300px';
  }

  const eventTimelineWindowSelect = document.getElementById('eventTimelineWindowSelect');
  if (eventTimelineWindowSelect && eventTimelineWindowSelect.dataset.initialized !== '1') {
    eventTimelineWindowSelect.dataset.initialized = '1';
    eventTimelineWindowSelect.addEventListener('change', function () {
      window.eventTimelineWindowHours = getSelectedEventTimelineWindowHours();
      updateEventTimeline24hChart();
    });
  }

  const eventTimelineData = Array.isArray(window.eventTimelineLast24h) ? window.eventTimelineLast24h : [];
  const hasEventPoints = eventTimelineData.some(function (timelineEntry) {
    const onlineHours = Number(timelineEntry.onlineHours) || 0;
    const standbyHours = Number(timelineEntry.standbyHours) || 0;
    return (Array.isArray(timelineEntry.points) && timelineEntry.points.length > 0) || onlineHours > 0 || standbyHours > 0;
  });

  if (!hasEventPoints) {
    const container = eventTimelineCanvas.parentElement;
    if (container) {
      container.innerHTML = '<div class="event-timeline-empty">' + mdsLabel('noEvents', 'No wakeup or standby events available yet.') + '</div>';
    }
    return;
  }

  window.eventTimeline24hChart = new Chart(eventTimelineCanvas, {
    type: 'line',
    data: {
      datasets: []
    },
    options: {
      maintainAspectRatio: false,
      responsive: true,
      interaction: {
        mode: 'nearest',
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
            label: function (context) {
              const rawPoint = context.raw || {};
              const stateLabel = Number(rawPoint.y) === 1 ? mdsLabel('onlineSuffix', 'Online') : mdsLabel('standbySuffix', 'Standby');
              return context.dataset.label + ': ' + stateLabel + ' · ' + (rawPoint.timestamp || rawPoint.x || '');
            }
          }
        }
      },
      scales: {
        x: {
          type: 'linear',
          grid: {
            color: 'rgba(148, 163, 184, 0.14)',
          },
          ticks: {
            color: '#475569',
            maxRotation: 0,
            callback: function (value) {
              return formatEventTimelineAxisLabel(value);
            },
          },
        },
        y: {
          min: -0.15,
          max: 1.15,
          ticks: {
            stepSize: 1,
            color: '#64748b',
            callback: function (value) {
              if (Number(value) === 1) {
                return mdsLabel('onlineSuffix', 'Online');
              }
              if (Number(value) === 0) {
                return mdsLabel('standbySuffix', 'Standby');
              }
              return '';
            }
          },
          grid: {
            color: 'rgba(148, 163, 184, 0.18)',
          }
        }
      }
    }
  });

  updateEventTimeline24hChart();
}

function updateEventTimeline24hChart() {
  if (!window.eventTimeline24hChart) {
    return;
  }

  const eventTimelineData = Array.isArray(window.eventTimelineLast24h) ? window.eventTimelineLast24h : [];
  const visibleTimelines = eventTimelineData.filter(function (timelineEntry) {
    return chartBoardVisibility.events.get(String(timelineEntry.boardId)) !== false;
  });
  const selectedWindowHours = getSelectedEventTimelineWindowHours();
  const windowEndMs = getEventTimelineEndMs(eventTimelineData);
  const windowStartMs = windowEndMs - (selectedWindowHours * 60 * 60 * 1000);
  const visibleWindowTimelines = visibleTimelines.map(function (timelineEntry) {
    return clipEventTimelineEntry(timelineEntry, windowStartMs, windowEndMs, selectedWindowHours);
  });

  const canvas = document.getElementById('eventTimeline24hCanvas');
  const eventTimelineShell = canvas ? canvas.closest('.event-summary-chart-shell') : null;
  let emptyState = eventTimelineShell ? eventTimelineShell.querySelector('.event-timeline-empty.is-chart-empty') : null;
  const datasets = [];
  updateEventTimelineWindowSummary(visibleWindowTimelines);

  visibleWindowTimelines.forEach(function (timelineEntry) {
    if (!Array.isArray(timelineEntry.points) || timelineEntry.points.length === 0) {
      return;
    }

    const boardId = String(timelineEntry.boardId);
    const boardColor = getEventSeriesColor(boardId);
    const timelinePoints = timelineEntry.points.map(function (point) {
      return {
        x: parseEventTimelineTimestamp(point.x || point.timestamp),
        y: Number(point.y),
        timestamp: point.timestamp,
      };
    }).filter(function (point) {
      return Number.isFinite(point.x) && Number.isFinite(point.y);
    });

    if (timelinePoints.length === 0) {
      return;
    }

    datasets.push({
      label: timelineEntry.boardName,
      data: timelinePoints,
      borderColor: boardColor.wakeupBorder,
      backgroundColor: boardColor.wakeupFill,
      pointBackgroundColor: boardColor.wakeupBorder,
      pointBorderColor: '#ffffff',
      pointBorderWidth: 1,
      pointRadius: 4,
      pointHoverRadius: 6,
      stepped: true,
      tension: 0,
      borderWidth: 2,
      boardId: boardId,
    });
  });

  window.eventTimeline24hChart.data.datasets = datasets;
  window.eventTimeline24hChart.options.scales.x.min = windowStartMs;
  window.eventTimeline24hChart.options.scales.x.max = windowEndMs;
  window.eventTimeline24hChart.update();

  if (datasets.length === 0) {
    if (eventTimelineShell && !emptyState) {
      emptyState = document.createElement('div');
      emptyState.className = 'event-timeline-empty is-chart-empty';
      emptyState.textContent = mdsLabel('noSelectedEvents', 'No ESP events are available for the currently selected devices.');
      eventTimelineShell.appendChild(emptyState);
    }
    if (emptyState) {
      emptyState.hidden = false;
    }
  } else if (emptyState) {
    emptyState.hidden = true;
  }
}

function getSelectedEventTimelineWindowHours() {
  const eventTimelineWindowSelect = document.getElementById('eventTimelineWindowSelect');
  const selectedHours = Number.parseInt(eventTimelineWindowSelect ? eventTimelineWindowSelect.value : window.eventTimelineWindowHours, 10);
  return [3, 6, 12, 24, 48, 72].includes(selectedHours) ? selectedHours : 24;
}

function getEventTimelineEndMs(eventTimelineData) {
  let windowEndMs = 0;
  eventTimelineData.forEach(function (timelineEntry) {
    if (!Array.isArray(timelineEntry.points)) {
      return;
    }
    timelineEntry.points.forEach(function (point) {
      const pointTime = parseEventTimelineTimestamp(point.x || point.timestamp);
      if (Number.isFinite(pointTime) && pointTime > windowEndMs) {
        windowEndMs = pointTime;
      }
    });
  });

  return windowEndMs > 0 ? windowEndMs : Date.now();
}

function clipEventTimelineEntry(timelineEntry, windowStartMs, windowEndMs, selectedWindowHours) {
  const rawPoints = Array.isArray(timelineEntry.points) ? timelineEntry.points : [];
  const parsedPoints = rawPoints.map(function (point) {
    return {
      x: parseEventTimelineTimestamp(point.x || point.timestamp),
      y: Number(point.y),
      timestamp: point.timestamp,
    };
  }).filter(function (point) {
    return Number.isFinite(point.x) && Number.isFinite(point.y);
  }).sort(function (leftPoint, rightPoint) {
    return leftPoint.x - rightPoint.x;
  });

  let lastPointBeforeWindow = null;
  const clippedPoints = [];

  parsedPoints.forEach(function (point) {
    if (point.x <= windowStartMs) {
      lastPointBeforeWindow = point;
      return;
    }
    if (point.x <= windowEndMs) {
      clippedPoints.push(point);
    }
  });

  if (lastPointBeforeWindow) {
    clippedPoints.unshift({
      x: windowStartMs,
      y: lastPointBeforeWindow.y,
      timestamp: formatEventTimelineTimestamp(windowStartMs),
    });
  }

  if (clippedPoints.length > 0) {
    const lastPoint = clippedPoints[clippedPoints.length - 1];
    if (lastPoint.x < windowEndMs) {
      clippedPoints.push({
        x: windowEndMs,
        y: lastPoint.y,
        timestamp: formatEventTimelineTimestamp(windowEndMs),
      });
    }
  }

  const deduplicatedPoints = [];
  clippedPoints.forEach(function (point) {
    const previousPoint = deduplicatedPoints[deduplicatedPoints.length - 1];
    if (previousPoint && previousPoint.x === point.x && previousPoint.y === point.y) {
      return;
    }
    deduplicatedPoints.push(point);
  });

  const windowStats = calculateEventWindowStats(deduplicatedPoints, selectedWindowHours);
  return {
    boardId: timelineEntry.boardId,
    boardName: timelineEntry.boardName,
    points: deduplicatedPoints,
    onlineHours: windowStats.onlineHours,
    standbyHours: windowStats.standbyHours,
    windowHours: selectedWindowHours,
    onlinePercent: windowStats.onlinePercent,
    standbyPercent: windowStats.standbyPercent,
  };
}

function calculateEventWindowStats(points, selectedWindowHours) {
  let onlineMs = 0;
  let standbyMs = 0;

  for (let pointIndex = 0; pointIndex < points.length - 1; pointIndex++) {
    const segmentStart = points[pointIndex];
    const segmentEnd = points[pointIndex + 1];
    const segmentMs = Math.max(0, segmentEnd.x - segmentStart.x);
    if (Number(segmentStart.y) === 1) {
      onlineMs += segmentMs;
    } else if (Number(segmentStart.y) === 0) {
      standbyMs += segmentMs;
    }
  }

  const windowMs = Math.max(1, selectedWindowHours * 60 * 60 * 1000);
  return {
    onlineHours: Math.round((onlineMs / 3600000) * 100) / 100,
    standbyHours: Math.round((standbyMs / 3600000) * 100) / 100,
    onlinePercent: Math.round((onlineMs / windowMs) * 1000) / 10,
    standbyPercent: Math.round((standbyMs / windowMs) * 1000) / 10,
  };
}

function updateEventTimelineWindowSummary(visibleTimelines) {
  const summaryContainer = document.getElementById('eventTimelineWindowSummary');
  if (!summaryContainer) {
    return;
  }

  const summaryItems = visibleTimelines.filter(function (timelineEntry) {
    const onlineHours = Number(timelineEntry.onlineHours) || 0;
    const standbyHours = Number(timelineEntry.standbyHours) || 0;
    return (Array.isArray(timelineEntry.points) && timelineEntry.points.length > 0) || onlineHours > 0 || standbyHours > 0;
  });

  if (summaryItems.length === 0) {
    summaryContainer.innerHTML = '';
    return;
  }

  summaryContainer.innerHTML = summaryItems.map(function (timelineEntry) {
    const onlineHours = Number(timelineEntry.onlineHours) || 0;
    const standbyHours = Number(timelineEntry.standbyHours) || 0;
    const windowHours = Number(timelineEntry.windowHours) || Number(window.eventTimelineWindowHours) || 24;
    const onlinePercent = Math.max(0, Math.min(100, Number(timelineEntry.onlinePercent) || 0));
    const standbyPercent = Math.max(0, Math.min(100, Number(timelineEntry.standbyPercent) || 0));

    return [
      '<article class="event-window-card">',
      '<h5>' + escapeHtml(timelineEntry.boardName || '-') + '</h5>',
      '<div class="event-window-metrics">',
      '<div class="event-window-metric"><strong>' + onlineHours.toFixed(2) + ' h</strong><span>' + mdsLabel('onlineHours', 'Online hours') + '</span></div>',
      '<div class="event-window-metric"><strong>' + standbyHours.toFixed(2) + ' h</strong><span>' + mdsLabel('standbyHours', 'Standby hours') + '</span></div>',
      '<div class="event-window-metric"><strong>' + windowHours.toFixed(0) + ' h</strong><span>' + mdsLabel('windowHours', 'Window') + '</span></div>',
      '</div>',
      '<div class="event-window-bar" aria-hidden="true">',
      '<span class="event-window-bar-online" style="width:' + onlinePercent.toFixed(1) + '%"></span>',
      '<span class="event-window-bar-standby" style="width:' + standbyPercent.toFixed(1) + '%"></span>',
      '</div>',
      '</article>'
    ].join('');
  }).join('');
}

function escapeHtml(value) {
  return String(value || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function formatEventTimelineAxisLabel(timestamp) {
  if (typeof timestamp === 'number' && Number.isFinite(timestamp)) {
    return new Date(timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  const timestampText = String(timestamp || '');
  const match = timestampText.match(/(\d{2}):(\d{2})(?::\d{2})?/);
  if (match) {
    return match[1] + ':' + match[2];
  }

  const parsedDate = new Date(timestampText);
  if (!Number.isNaN(parsedDate.getTime())) {
    return parsedDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  return timestampText;
}

function formatEventTimelineTimestamp(timestamp) {
  return new Date(timestamp).toLocaleString([], {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

function parseEventTimelineTimestamp(timestamp) {
  if (typeof timestamp === 'number' && Number.isFinite(timestamp)) {
    return timestamp;
  }

  const timestampText = String(timestamp || '').trim();
  if (timestampText === '') {
    return NaN;
  }

  const parsedDate = new Date(timestampText);
  if (!Number.isNaN(parsedDate.getTime())) {
    return parsedDate.getTime();
  }

  const germanMatch = timestampText.match(/^(\d{2})\.(\d{2})\.(\d{4})\s+(\d{2}):(\d{2})(?::(\d{2}))?$/);
  if (!germanMatch) {
    return NaN;
  }

  return new Date(
    Number(germanMatch[3]),
    Number(germanMatch[2]) - 1,
    Number(germanMatch[1]),
    Number(germanMatch[4]),
    Number(germanMatch[5]),
    Number(germanMatch[6] || 0)
  ).getTime();
}

function updateEventSummaryChart() {
  if (!window.eventSummaryChart) {
    return;
  }

  const eventSummaryData = Array.isArray(window.eventTimelineSummary) ? window.eventTimelineSummary : [];
  const visibleSummaries = eventSummaryData.filter(function (summaryEntry) {
    return chartBoardVisibility.events.get(String(summaryEntry.boardId)) !== false;
  });

  const canvas = document.getElementById('eventSummaryCanvas');
  const eventSummaryShell = canvas ? canvas.closest('.event-summary-chart-shell') : null;
  let emptyState = eventSummaryShell ? eventSummaryShell.querySelector('.event-timeline-empty.is-chart-empty') : null;

  if (visibleSummaries.length === 0) {
    window.eventSummaryChart.data.datasets = [];
    window.eventSummaryChart.update();

    if (eventSummaryShell && !emptyState) {
      emptyState = document.createElement('div');
      emptyState.className = 'event-timeline-empty is-chart-empty';
      emptyState.textContent = mdsLabel('noSelectedEvents', 'No ESP events are available for the currently selected devices.');
      eventSummaryShell.appendChild(emptyState);
    }
    if (emptyState) {
      emptyState.hidden = false;
    }
    return;
  }

  if (emptyState) {
    emptyState.hidden = true;
  }

  const datasets = [];
  visibleSummaries.forEach(function (summaryEntry) {
    const boardId = String(summaryEntry.boardId);
    const boardColor = getEventSeriesColor(boardId);
    datasets.push({
      label: summaryEntry.boardName + ' ' + mdsLabel('onlineSuffix', 'Online'),
      data: Array.isArray(summaryEntry.onlineDailyHours) ? summaryEntry.onlineDailyHours.map(Number) : [],
      borderColor: boardColor.wakeupBorder,
      backgroundColor: boardColor.wakeupFill,
      borderWidth: 1,
      borderRadius: 6,
      maxBarThickness: 20,
      boardId: boardId,
    });
    datasets.push({
      label: summaryEntry.boardName + ' ' + mdsLabel('standbySuffix', 'Standby'),
      data: Array.isArray(summaryEntry.standbyDailyHours) ? summaryEntry.standbyDailyHours.map(Number) : [],
      borderColor: boardColor.standbyBorder,
      backgroundColor: boardColor.standbyFill,
      borderWidth: 1,
      borderRadius: 6,
      maxBarThickness: 20,
      boardId: boardId,
    });
  });
  window.eventSummaryChart.data.labels = Array.isArray(window.eventTimelineSummaryLabels) ? window.eventTimelineSummaryLabels : [];
  window.eventSummaryChart.data.datasets = datasets;
  window.eventSummaryChart.update();
}

function getEventSeriesColor(boardId) {
  const palette = [
    {
      wakeupBorder: 'rgba(22, 163, 74, 1)',
      wakeupFill: 'rgba(22, 163, 74, 0.22)',
      standbyBorder: 'rgba(245, 158, 11, 1)',
      standbyFill: 'rgba(245, 158, 11, 0.18)',
    },
    {
      wakeupBorder: 'rgba(37, 99, 235, 1)',
      wakeupFill: 'rgba(37, 99, 235, 0.22)',
      standbyBorder: 'rgba(124, 58, 237, 1)',
      standbyFill: 'rgba(124, 58, 237, 0.18)',
    },
    {
      wakeupBorder: 'rgba(14, 165, 233, 1)',
      wakeupFill: 'rgba(14, 165, 233, 0.22)',
      standbyBorder: 'rgba(249, 115, 22, 1)',
      standbyFill: 'rgba(249, 115, 22, 0.18)',
    },
    {
      wakeupBorder: 'rgba(236, 72, 153, 1)',
      wakeupFill: 'rgba(236, 72, 153, 0.22)',
      standbyBorder: 'rgba(225, 29, 72, 1)',
      standbyFill: 'rgba(225, 29, 72, 0.18)',
    }
  ];
  const numericBoardId = Number(boardId) || 0;
  return palette[numericBoardId % palette.length];
}

function refreshChartsTabViews() {
  if (!window.eventTimeline24hChart) {
    initializeEventTimeline24hChart();
  }
  if (!window.eventSummaryChart) {
    initializeEventSummaryChart();
  }
  ['myChart', 'myChart2', 'myChart3', 'eventTimeline24hChart', 'eventSummaryChart'].forEach(function (chartName) {
    const chartInstance = window[chartName];
    if (!chartInstance) {
      return;
    }
    if (typeof chartInstance.resize === 'function') {
      chartInstance.resize();
    }
    if (typeof chartInstance.update === 'function') {
      chartInstance.update();
    }
  });
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
      emptyState.textContent = mdsLabel('noSelectedEvents', 'No ESP events are available for the currently selected devices.');
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
