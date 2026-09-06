
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

function mdsIsMobileChartViewport() {
  return window.matchMedia && window.matchMedia('(max-width: 767.98px)').matches;
}

function mdsFormatChartXAxisLabel(label) {
  const value = String(label || '');
  if (!mdsIsMobileChartViewport()) {
    return value;
  }

  const parts = value.split(' ');
  if (parts.length >= 2) {
    return parts[1].substring(0, 5);
  }
  return value.length > 8 ? value.substring(0, 8) : value;
}

function mdsLineChartOptions() {
  const isMobile = mdsIsMobileChartViewport();
  return {
    maintainAspectRatio: false,
    responsive: true,
    resizeDelay: 120,
    interaction: {
      mode: 'nearest',
      intersect: false,
      axis: 'x',
    },
    elements: {
      line: {
        borderWidth: isMobile ? 2.5 : 2,
        tension: 0.25,
      },
      point: {
        radius: isMobile ? 0 : 1.8,
        hoverRadius: 5,
        hitRadius: 12,
      },
    },
    plugins: {
      legend: {
        display: !isMobile,
        position: 'bottom',
        labels: {
          usePointStyle: true,
          boxWidth: 8,
          boxHeight: 8,
          color: '#334155',
          font: {
            size: 11,
            weight: '600',
          },
        },
      },
      tooltip: {
        mode: 'nearest',
        intersect: false,
        callbacks: {
          title: function (items) {
            return items.length ? new Date(items[0].parsed.x).toLocaleString() : '';
          },
        },
      },
    },
    scales: {
      x: {
        type: 'linear',
        grid: {
          display: false,
        },
        ticks: {
          autoSkip: true,
          maxTicksLimit: isMobile ? 4 : 10,
          maxRotation: 0,
          color: '#64748b',
          font: {
            size: isMobile ? 10 : 12,
          },
          callback: function (value) {
            const hasVisibleData = this.chart.data.datasets.some((dataset, index) =>
              this.chart.isDatasetVisible(index) && dataset.data.length > 0);
            if (!hasVisibleData) return '';
            return new Date(value).toLocaleString(undefined, {
              day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit',
            });
          },
        },
      },
      y: {
        ticks: {
          maxTicksLimit: isMobile ? 5 : 8,
          color: '#475569',
          font: {
            size: isMobile ? 10 : 12,
          },
        },
        grid: {
          color: 'rgba(148, 163, 184, 0.16)',
        },
      },
    },
  };
}

function mdsEventChartHeight() {
  return mdsIsMobileChartViewport() ? '280px' : '320px';
}

function mdsEventTimelineChartHeight() {
  return mdsIsMobileChartViewport() ? '280px' : '300px';
}

function mdsCompactEventLaneLabel(label) {
  const value = String(label || '');
  if (!mdsIsMobileChartViewport() || value.length <= 14) {
    return value;
  }
  return value.substring(0, 13) + '…';
}

function mdsEventChartInner(canvasId) {
  const canvas = document.getElementById(canvasId);
  return canvas ? canvas.closest('.event-chart-scroll-inner') : null;
}

function mdsSetEventTimelineChartHeight(visibleLaneCount) {
  const eventTimelineInner = mdsEventChartInner('eventTimeline24hCanvas');
  if (!eventTimelineInner) {
    return;
  }

  const baseHeight = mdsIsMobileChartViewport() ? 280 : 320;
  const laneHeight = mdsIsMobileChartViewport() ? 48 : 64;
  const verticalPadding = mdsIsMobileChartViewport() ? 90 : 120;
  const dynamicHeight = Math.max(baseHeight, (Math.max(1, visibleLaneCount) * laneHeight) + verticalPadding);
  eventTimelineInner.style.height = dynamicHeight + 'px';
}

// Load each sensor group once per refresh, sharing its rows across all channels.
let sensorChartsRefreshPromise = null;

$(document).ready(function () {
  InitialSetupChart();
  initializeChartBoardFilters();
  initializeEventTimeline();
  refreshSensorCharts();
  document.addEventListener('visibilitychange', function () {
    if (!document.hidden && document.getElementById('charts')?.classList.contains('active')) {
      refreshSensorCharts();
    }
  });
});

function sensorChartTimestamp(row) {
  return typeof row.timestamp === 'number' && Number.isFinite(row.timestamp) ? row.timestamp : NaN;
}

function sensorChartPoints(rows, channelNr) {
  const points = new Map();
  rows.forEach(function (row) {
    const x = sensorChartTimestamp(row);
    const raw = row['value' + channelNr];
    const value = (typeof raw === 'number' || typeof raw === 'string') && String(raw).trim() !== ''
      ? Number(raw) : NaN;
    if (Number.isFinite(x)) {
      points.set(x, { x: x, y: Number.isFinite(value) ? value : null });
    }
  });
  return Array.from(points.values()).sort(function (a, b) { return a.x - b.x; });
}

function updateSensorChartDataset(info, rows) {
  const chartKey = getChartKeyForDataset(info);
  const chart = getChartInstance(chartKey);
  if (!chart) {
    return;
  }
  const key = String(info.sensorId) + ':' + String(info.channelNr);
  let dataset = chart.data.datasets.find(function (entry) { return entry.sensorChannelKey === key; });
  if (!dataset) {
    dataset = {
      sensorChannelKey: key,
      label: info.BoardName + '.' + info.NameOfSensors,
      backgroundColor: info.ChartColor,
      borderColor: info.ChartColor,
      fill: false,
      spanGaps: false,
      boardId: String(info.BoardId),
      boardName: info.BoardName,
      chartKey: chartKey,
      hidden: chartBoardVisibility[chartKey].get(String(info.BoardId)) === false,
    };
    chart.data.datasets.push(dataset);
  }
  dataset.data = sensorChartPoints(rows, info.channelNr);
  chart.update('none');
}

function refreshSensorCharts() {
  if (sensorChartsRefreshPromise) {
    return sensorChartsRefreshPromise;
  }
  const groups = new Map();
  (typeof gaugesArrayHelperBig !== 'undefined' ? gaugesArrayHelperBig : []).forEach(function (info) {
    const id = String(info.sensorId);
    if (!groups.has(id)) {
      groups.set(id, []);
    }
    groups.get(id).push(info);
  });
  const queue = Array.from(groups.entries());
  async function worker() {
    while (queue.length) {
      const [sensorId, channels] = queue.shift();
      try {
        const rows = await $.ajax({
          url: 'api/getSensorDataSet.php',
          data: { sensorId: sensorId, days: getSensorChartWindowDays(), maxValues: 1000 },
          dataType: 'json',
          cache: false,
          timeout: 20000,
        });
        if (!Array.isArray(rows)) {
          throw new Error('Invalid sensor history response');
        }
        channels.forEach(function (info) { updateSensorChartDataset(info, rows); });
      } catch (error) {
        // Preserve the previous series on transient failures instead of clearing it.
        console.error('Sensor chart refresh failed for sensor ' + sensorId, error);
      }
    }
  }
  sensorChartsRefreshPromise = Promise.all(Array.from({ length: Math.min(4, queue.length) }, worker))
    .finally(function () { sensorChartsRefreshPromise = null; });
  return sensorChartsRefreshPromise;
}

function getSensorChartWindowDays() {
  const days = Number.parseInt(window.preferredChartWindowDays || '7', 10);
  return [1, 7, 14, 30].includes(days) ? days : 7;
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

    filterContainer.replaceChildren();
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
  if (datasetInfo.typename === 'DS18B20'
      || (datasetInfo.typename === 'BME280' && Number(datasetInfo.channelNr) === 1)) {
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
    renderEventTimelineEmptyState(eventTimelineContainer, mdsLabel('noEvents', 'No wakeup or standby events available yet.'));
    return;
  }
}

function initializeEventSummaryChart() {
  const eventSummaryCanvas = document.getElementById('eventSummaryCanvas');
  if (!eventSummaryCanvas) {
    return;
  }

  const eventSummaryShell = eventSummaryCanvas.closest('.event-summary-chart-shell');
  const eventSummaryInner = mdsEventChartInner('eventSummaryCanvas');
  if (eventSummaryInner) {
    eventSummaryInner.style.height = mdsEventChartHeight();
  }

  const eventSummaryData = Array.isArray(window.eventTimelineSummary) ? window.eventTimelineSummary : [];
  const eventSummaryLabels = Array.isArray(window.eventTimelineSummaryLabels) ? window.eventTimelineSummaryLabels : [];
  if (eventSummaryData.length === 0) {
    const container = eventSummaryCanvas.parentElement;
    if (container) {
      renderEventTimelineEmptyState(container, mdsLabel('noEvents', 'No wakeup or standby events available yet.'));
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
          display: !mdsIsMobileChartViewport(),
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
          stacked: true,
          grid: {
            display: false,
          },
          ticks: {
            color: '#475569',
            maxTicksLimit: mdsIsMobileChartViewport() ? 4 : 10,
            font: {
              weight: '600',
            }
          }
        },
        y: {
          stacked: true,
          beginAtZero: true,
          ticks: {
            color: '#64748b',
            maxTicksLimit: mdsIsMobileChartViewport() ? 5 : 8,
            callback: function (value) {
              return Number(value).toFixed(1) + ' h';
            }
          },
          grid: {
            color: 'rgba(148, 163, 184, 0.18)',
          },
          title: {
            display: !mdsIsMobileChartViewport(),
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
  const eventTimelineInner = mdsEventChartInner('eventTimeline24hCanvas');
  if (eventTimelineInner) {
    eventTimelineInner.style.height = mdsEventTimelineChartHeight();
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
      renderEventTimelineEmptyState(container, mdsLabel('noEvents', 'No wakeup or standby events available yet.'));
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
          display: !mdsIsMobileChartViewport(),
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
              const stateLabel = Number(rawPoint.state) === 1 ? mdsLabel('onlineSuffix', 'Online') : mdsLabel('standbySuffix', 'Standby');
              const openLabel = rawPoint.openEnded ? ' · ' + mdsLabel('openEnded', 'open / not final') : '';
              return context.dataset.label + ': ' + stateLabel + openLabel + ' · ' + (rawPoint.timestamp || rawPoint.x || '');
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
            maxTicksLimit: mdsIsMobileChartViewport() ? 4 : 10,
            callback: function (value) {
              return formatEventTimelineAxisLabel(value);
            },
          },
        },
        y: {
          min: -0.5,
          max: 0.5,
          ticks: {
            stepSize: 1,
            color: '#64748b',
            callback: function (value) {
              const laneIndex = Math.round(Number(value));
              if (Math.abs(Number(value) - laneIndex) > 0.01) {
                return '';
              }
              const laneLabel = (window.eventTimelineLaneLabels && window.eventTimelineLaneLabels[laneIndex]) ? window.eventTimelineLaneLabels[laneIndex] : '';
              return mdsCompactEventLaneLabel(laneLabel);
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
  window.eventTimelineLaneLabels = visibleWindowTimelines.map(function (timelineEntry) {
    return timelineEntry.boardName || '-';
  });
  mdsSetEventTimelineChartHeight(visibleWindowTimelines.length);

  const canvas = document.getElementById('eventTimeline24hCanvas');
  const eventTimelineShell = canvas ? canvas.closest('.event-summary-chart-shell') : null;
  let emptyState = eventTimelineShell ? eventTimelineShell.querySelector('.event-timeline-empty.is-chart-empty') : null;
  const datasets = [];
  updateEventTimelineWindowSummary(visibleWindowTimelines);

  visibleWindowTimelines.forEach(function (timelineEntry, laneIndex) {
    if (!Array.isArray(timelineEntry.points) || timelineEntry.points.length === 0) {
      return;
    }

    const boardId = String(timelineEntry.boardId);
    const boardColor = getEventSeriesColor(boardId);
    const timelinePoints = timelineEntry.points.map(function (point) {
      return {
        x: parseEventTimelineTimestamp(point.x || point.timestamp),
        y: laneIndex + (Number(point.y) === 1 ? 0.18 : -0.18),
        state: Number(point.y),
        timestamp: point.timestamp,
        openEnded: point.openEnded === true,
        persistentOnline: point.persistentOnline === true,
        gapAfter: point.gapAfter === true,
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
      pointBackgroundColor: function (context) {
        return Number(context.raw?.state) === 1 ? boardColor.wakeupBorder : boardColor.standbyBorder;
      },
      pointBorderColor: '#ffffff',
      pointBorderWidth: 1,
      pointRadius: mdsIsMobileChartViewport() ? 3 : 4,
      pointHoverRadius: 6,
      stepped: true,
      tension: 0,
      borderWidth: mdsIsMobileChartViewport() ? 3 : 2.25,
      segment: {
        borderColor: function (context) {
          if (context.p0.raw?.gapAfter === true) {
            return 'rgba(148, 163, 184, 0.3)';
          }
          return Number(context.p0.raw?.state) === 1 ? boardColor.wakeupBorder : boardColor.standbyBorder;
        },
        borderDash: function (context) {
          return context.p0.raw?.gapAfter === true ? [5, 6] : [];
        },
        backgroundColor: function (context) {
          return Number(context.p0.raw?.state) === 1 ? boardColor.wakeupFill : boardColor.standbyFill;
        },
      },
      boardId: boardId,
    });
  });

  window.eventTimeline24hChart.data.datasets = datasets;
  window.eventTimeline24hChart.options.scales.x.min = windowStartMs;
  window.eventTimeline24hChart.options.scales.x.max = windowEndMs;
  window.eventTimeline24hChart.options.scales.y.min = -0.5;
  window.eventTimeline24hChart.options.scales.y.max = Math.max(0.5, visibleWindowTimelines.length - 0.5);
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

  return Math.max(Date.now(), windowEndMs);
}

function clipEventTimelineEntry(timelineEntry, windowStartMs, windowEndMs, selectedWindowHours) {
  const rawPoints = Array.isArray(timelineEntry.points) ? timelineEntry.points : [];
  const parsedPoints = rawPoints.map(function (point) {
    return {
      x: parseEventTimelineTimestamp(point.x || point.timestamp),
      y: Number(point.y),
      timestamp: point.timestamp,
      openEnded: point.openEnded === true,
      persistentOnline: point.persistentOnline === true,
      gapAfter: point.gapAfter === true,
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

  if (lastPointBeforeWindow && lastPointBeforeWindow.gapAfter !== true) {
    clippedPoints.unshift({
      x: windowStartMs,
      y: lastPointBeforeWindow.y,
      timestamp: formatEventTimelineTimestamp(windowStartMs),
      openEnded: lastPointBeforeWindow.openEnded === true,
      persistentOnline: lastPointBeforeWindow.persistentOnline === true,
      gapAfter: false,
    });
  }

  if (clippedPoints.length > 0) {
    const lastPoint = clippedPoints[clippedPoints.length - 1];
    if (lastPoint.x < windowEndMs && Number(lastPoint.y) !== 1) {
      clippedPoints.push({
        x: windowEndMs,
        y: lastPoint.y,
        timestamp: formatEventTimelineTimestamp(windowEndMs),
      });
    } else if (Number(lastPoint.y) === 1 && lastPoint.persistentOnline !== true) {
      lastPoint.openEnded = true;
    }
  }

  const deduplicatedPoints = [];
  clippedPoints.forEach(function (point) {
    const previousPoint = deduplicatedPoints[deduplicatedPoints.length - 1];
    if (previousPoint && previousPoint.x === point.x && previousPoint.y === point.y) {
      previousPoint.gapAfter = previousPoint.gapAfter === true || point.gapAfter === true;
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
    unknownHours: windowStats.unknownHours,
    windowHours: selectedWindowHours,
    onlinePercent: windowStats.onlinePercent,
    standbyPercent: windowStats.standbyPercent,
    unknownPercent: windowStats.unknownPercent,
  };
}

function calculateEventWindowStats(points, selectedWindowHours) {
  let onlineMs = 0;
  let standbyMs = 0;

  for (let pointIndex = 0; pointIndex < points.length - 1; pointIndex++) {
    const segmentStart = points[pointIndex];
    const segmentEnd = points[pointIndex + 1];
    const segmentMs = Math.max(0, segmentEnd.x - segmentStart.x);
    if (segmentStart.gapAfter === true) {
      continue;
    }
    if (Number(segmentStart.y) === 1) {
      onlineMs += segmentMs;
    } else if (Number(segmentStart.y) === 0) {
      standbyMs += segmentMs;
    }
  }

  const windowMs = Math.max(1, selectedWindowHours * 60 * 60 * 1000);
  const unknownMs = Math.max(0, windowMs - onlineMs - standbyMs);
  return {
    onlineHours: Math.round((onlineMs / 3600000) * 100) / 100,
    standbyHours: Math.round((standbyMs / 3600000) * 100) / 100,
    unknownHours: Math.round((unknownMs / 3600000) * 100) / 100,
    onlinePercent: Math.round((onlineMs / windowMs) * 1000) / 10,
    standbyPercent: Math.round((standbyMs / windowMs) * 1000) / 10,
    unknownPercent: Math.round((unknownMs / windowMs) * 1000) / 10,
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
    const unknownHours = Number(timelineEntry.unknownHours) || 0;
    return (Array.isArray(timelineEntry.points) && timelineEntry.points.length > 0) || onlineHours > 0 || standbyHours > 0 || unknownHours > 0;
  });

  if (summaryItems.length === 0) {
    summaryContainer.replaceChildren();
    return;
  }

  const summaryCards = summaryItems.map(function (timelineEntry) {
    const onlineHours = Number(timelineEntry.onlineHours) || 0;
    const standbyHours = Number(timelineEntry.standbyHours) || 0;
    const unknownHours = Number(timelineEntry.unknownHours) || 0;
    const windowHours = Number(timelineEntry.windowHours) || Number(window.eventTimelineWindowHours) || 24;
    const onlinePercent = Math.max(0, Math.min(100, Number(timelineEntry.onlinePercent) || 0));
    const standbyPercent = Math.max(0, Math.min(100, Number(timelineEntry.standbyPercent) || 0));

    const card = document.createElement('article');
    card.className = 'event-window-card';

    const title = document.createElement('h5');
    title.textContent = timelineEntry.boardName || '-';
    card.appendChild(title);

    const metrics = document.createElement('div');
    metrics.className = 'event-window-metrics';
    metrics.appendChild(createEventWindowMetric(onlineHours.toFixed(2) + ' h', mdsLabel('onlineHours', 'Online hours')));
    metrics.appendChild(createEventWindowMetric(standbyHours.toFixed(2) + ' h', mdsLabel('standbyHours', 'Standby hours')));
    metrics.appendChild(createEventWindowMetric(unknownHours.toFixed(2) + ' h', mdsLabel('unknownHours', 'Unknown')));
    metrics.appendChild(createEventWindowMetric(windowHours.toFixed(0) + ' h', mdsLabel('windowHours', 'Window')));
    card.appendChild(metrics);

    const bar = document.createElement('div');
    bar.className = 'event-window-bar';
    bar.setAttribute('aria-hidden', 'true');

    const onlineBar = document.createElement('span');
    onlineBar.className = 'event-window-bar-online';
    onlineBar.style.width = onlinePercent.toFixed(1) + '%';
    bar.appendChild(onlineBar);

    const standbyBar = document.createElement('span');
    standbyBar.className = 'event-window-bar-standby';
    standbyBar.style.width = standbyPercent.toFixed(1) + '%';
    bar.appendChild(standbyBar);
    card.appendChild(bar);

    return card;
  });

  summaryContainer.replaceChildren(...summaryCards);
}

function createEventWindowMetric(value, label) {
  const metric = document.createElement('div');
  metric.className = 'event-window-metric';

  const strong = document.createElement('strong');
  strong.textContent = value;
  metric.appendChild(strong);

  const span = document.createElement('span');
  span.textContent = label;
  metric.appendChild(span);

  return metric;
}

function renderEventTimelineEmptyState(container, message) {
  const emptyState = document.createElement('div');
  emptyState.className = 'event-timeline-empty';
  emptyState.textContent = message;
  container.replaceChildren(emptyState);
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
      maxBarThickness: mdsIsMobileChartViewport() ? 28 : 22,
      stack: boardId,
      boardId: boardId,
    });
    datasets.push({
      label: summaryEntry.boardName + ' ' + mdsLabel('standbySuffix', 'Standby'),
      data: Array.isArray(summaryEntry.standbyDailyHours) ? summaryEntry.standbyDailyHours.map(Number) : [],
      borderColor: boardColor.standbyBorder,
      backgroundColor: boardColor.standbyFill,
      borderWidth: 1,
      borderRadius: 6,
      maxBarThickness: mdsIsMobileChartViewport() ? 28 : 22,
      stack: boardId,
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
  refreshResponsiveChartOptions();
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

function refreshResponsiveChartOptions() {
  ['myChart', 'myChart2', 'myChart3'].forEach(function (chartName) {
    if (window[chartName]) {
      window[chartName].options = mdsLineChartOptions();
    }
  });

  const eventSummaryInner = mdsEventChartInner('eventSummaryCanvas');
  if (eventSummaryInner) {
    eventSummaryInner.style.height = mdsEventChartHeight();
  }
  const eventTimelineInner = mdsEventChartInner('eventTimeline24hCanvas');
  if (eventTimelineInner) {
    mdsSetEventTimelineChartHeight(Array.isArray(window.eventTimelineLaneLabels) ? window.eventTimelineLaneLabels.length : 1);
  }

  if (window.eventSummaryChart) {
    window.eventSummaryChart.options.plugins.legend.display = !mdsIsMobileChartViewport();
    window.eventSummaryChart.options.scales.x.ticks.maxTicksLimit = mdsIsMobileChartViewport() ? 4 : 10;
    window.eventSummaryChart.options.scales.y.ticks.maxTicksLimit = mdsIsMobileChartViewport() ? 5 : 8;
    window.eventSummaryChart.options.scales.y.title.display = !mdsIsMobileChartViewport();
  }
  if (window.eventTimeline24hChart) {
    window.eventTimeline24hChart.options.plugins.legend.display = !mdsIsMobileChartViewport();
    window.eventTimeline24hChart.options.scales.x.ticks.maxTicksLimit = mdsIsMobileChartViewport() ? 4 : 10;
    window.eventTimeline24hChart.options.scales.y.max = Math.max(0.5, (Array.isArray(window.eventTimelineLaneLabels) ? window.eventTimelineLaneLabels.length : 1) - 0.5);
  }
}

window.addEventListener('resize', function () {
  window.clearTimeout(window.mdsChartResizeTimer);
  window.mdsChartResizeTimer = window.setTimeout(refreshChartsTabViews, 180);
});

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
    options: mdsLineChartOptions()
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
    options: mdsLineChartOptions()
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
    options: mdsLineChartOptions()
  });
}
