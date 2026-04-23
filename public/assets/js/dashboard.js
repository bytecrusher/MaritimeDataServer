/**
 * Dashboard bootstrap for gauge cards and dashboard interactions.
 */

$(document).ready(function () {
  initializeDashboardCards();
  initializeDashboardToolbar();
});

function initializeDashboardCards() {
  const gaugeCards = document.querySelectorAll('.dashboard-gauge-card[data-gauge-key]');
  gaugeCards.forEach(function (card) {
    const gaugeKey = card.dataset.gaugeKey;
    const sensorId = card.dataset.sensorId;
    const boardId = card.dataset.boardId;
    const boardName = card.dataset.boardName;
    const chartColor = card.dataset.chartColor;
    const typename = card.dataset.typename;
    const chartEntryName = card.dataset.sensorDisplayName;
    const channelNr = parseInt(card.dataset.channelNr || '1', 10);
    const gaugeMount = card.querySelector('.dashboard-gauge-visual');
    const gaugeValueNumber = card.querySelector('.dashboard-gauge-value-number');
    const minValue = parseFloat(card.dataset.min || '0');
    const maxValue = parseFloat(card.dataset.max || '100');
    const gaugeValue = parseFloat(card.dataset.value || '0');
    const lowThreshold = parseFloat(card.dataset.lowThreshold || String(minValue));
    const highThreshold = parseFloat(card.dataset.highThreshold || String(maxValue));
    const lowColor = card.dataset.lowColor || '#dc2626';
    const highColor = card.dataset.highColor || '#dc2626';
    const normalColor = card.dataset.normalColor || '#16a34a';

    if (!gaugeMount || !gaugeKey || !sensorId) {
      return;
    }

    const gaugeInstance = Gauge(gaugeMount, {
      min: minValue,
      max: maxValue,
      dialStartAngle: 180,
      dialEndAngle: 0,
      value: Number.isFinite(gaugeValue) ? gaugeValue : minValue,
      viewBox: '0 0 100 57',
      id: gaugeKey,
      color: function (value) {
        if (value < lowThreshold) {
          return lowColor;
        }
        if (value < highThreshold) {
          return normalColor;
        }
        return highColor;
      },
    });

    gaugesArrayHelper.push(gaugeKey);
    gaugesMap.set(gaugeKey, gaugeInstance);

    SensorArrayHelper.push(parseInt(sensorId, 10));

    const chartMetadata = {
      sensorId: String(sensorId),
      typId: String(card.dataset.typId || ''),
      typename: typename,
      NrOfSensors: String(card.dataset.nrOfSensors || ''),
      channelNr: String(channelNr),
      NameOfSensors: chartEntryName,
      ChartColor: chartColor,
      BoardId: String(boardId || ''),
      BoardName: boardName,
      onDashboard: '1',
    };
    gaugesArrayHelperBig.push(chartMetadata);
  });
}

function initializeDashboardToolbar() {
  const onlineToggle = document.getElementById('dashboard-online-only-toggle');
  if (!onlineToggle) {
    return;
  }

  onlineToggle.addEventListener('change', function () {
    toggleDashboardOnlineOnly(onlineToggle.checked);
  });
}

function toggleDashboardOnlineOnly(onlineOnly) {
  document.querySelectorAll('[data-dashboard-board-id]').forEach(function (boardCard) {
    const isOnline = boardCard.dataset.dashboardOnline === '1';
    boardCard.classList.toggle('dashboard-board-hidden', onlineOnly && !isOnline);
  });
}
