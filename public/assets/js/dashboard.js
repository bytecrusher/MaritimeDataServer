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
    const gaugeStyle = card.dataset.gaugeStyle || 'classic';

    if (!gaugeMount || !gaugeKey || !sensorId) {
      return;
    }

    card.classList.add('dashboard-gauge-style-' + gaugeStyle);
    if (/^(#[0-9a-f]{3,8}|rgba?\([\d\s.,%]+\)|hsla?\([\d\s.,%]+\))$/i.test(normalColor)) {
      card.style.setProperty('--gauge-accent', normalColor);
    }

    const gaugeStyleConfig = getGaugeStyleConfig(gaugeStyle);

    const gaugeInstance = Gauge(gaugeMount, {
      min: minValue,
      max: maxValue,
      dialRadius: gaugeStyleConfig.dialRadius,
      dialStartAngle: gaugeStyleConfig.dialStartAngle,
      dialEndAngle: gaugeStyleConfig.dialEndAngle,
      value: Number.isFinite(gaugeValue) ? gaugeValue : minValue,
      viewBox: gaugeStyleConfig.viewBox,
      id: gaugeKey,
      gaugeClass: gaugeStyleConfig.gaugeClass,
      showValue: false,
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

function getGaugeStyleConfig(gaugeStyle) {
  switch (gaugeStyle) {
    case 'minimal':
      return {
        dialRadius: 40,
        dialStartAngle: 180,
        dialEndAngle: 0,
        viewBox: '0 0 100 57',
        gaugeClass: 'gauge gauge-style-minimal',
      };
    case 'bold':
      return {
        dialRadius: 40,
        dialStartAngle: 200,
        dialEndAngle: -20,
        viewBox: '0 0 100 62',
        gaugeClass: 'gauge gauge-style-bold',
      };
    case 'arc':
      return {
        dialRadius: 40,
        dialStartAngle: 220,
        dialEndAngle: -40,
        viewBox: '0 0 100 70',
        gaugeClass: 'gauge gauge-style-arc',
      };
    case 'ring':
      return {
        dialRadius: 40,
        dialStartAngle: 225,
        dialEndAngle: -45,
        viewBox: '0 0 100 100',
        gaugeClass: 'gauge gauge-style-ring',
      };
    case 'clock':
      return {
        dialRadius: 40,
        dialStartAngle: 269,
        dialEndAngle: -89,
        viewBox: '0 0 100 100',
        gaugeClass: 'gauge gauge-style-clock',
      };
    case 'industrial':
      return {
        dialRadius: 40,
        dialStartAngle: 240,
        dialEndAngle: -60,
        viewBox: '0 0 100 86',
        gaugeClass: 'gauge gauge-style-industrial',
      };
    case 'classic':
    default:
      return {
        dialRadius: 40,
        dialStartAngle: 180,
        dialEndAngle: 0,
        viewBox: '0 0 100 57',
        gaugeClass: 'gauge gauge-style-classic',
      };
  }
}

function initializeDashboardToolbar() {
  initializeDashboardGaugeToggles();
  initializeDashboardGaugeSize();

  const onlineToggle = document.getElementById('dashboard-online-only-toggle');
  if (!onlineToggle) {
    return;
  }

  toggleDashboardOnlineOnly(onlineToggle.checked);
  onlineToggle.addEventListener('change', function () {
    toggleDashboardOnlineOnly(onlineToggle.checked);
  });
}

function initializeDashboardGaugeSize() {
  const dashboard = document.getElementById('dashboard');
  const sizePicker = document.getElementById('dashboard-gauge-size-picker');
  if (!dashboard || !sizePicker) {
    return;
  }

  const allowedSizes = ['small', 'medium', 'large'];
  let selectedSize = 'medium';
  try {
    const storedSize = window.localStorage.getItem('mds.dashboard.gaugeSize');
    if (allowedSizes.includes(storedSize)) {
      selectedSize = storedSize;
    }
  } catch (error) {
    // The size picker remains functional when browser storage is unavailable.
  }

  setDashboardGaugeSize(dashboard, sizePicker, selectedSize);
  sizePicker.addEventListener('click', function (event) {
    const button = event.target.closest('[data-gauge-size]');
    if (!button || !sizePicker.contains(button)) {
      return;
    }

    const size = button.dataset.gaugeSize;
    if (!allowedSizes.includes(size)) {
      return;
    }

    setDashboardGaugeSize(dashboard, sizePicker, size);
    try {
      window.localStorage.setItem('mds.dashboard.gaugeSize', size);
    } catch (error) {
      // The selected size still applies for the current page view.
    }
  });
}

function setDashboardGaugeSize(dashboard, sizePicker, size) {
  dashboard.dataset.gaugeSize = size;
  sizePicker.querySelectorAll('[data-gauge-size]').forEach(function (button) {
    const isActive = button.dataset.gaugeSize === size;
    button.classList.toggle('is-active', isActive);
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
  });
}

function initializeDashboardGaugeToggles() {
  document.querySelectorAll('[data-dashboard-gauge-toggle]').forEach(function (toggle) {
    const boardId = toggle.dataset.boardId || '';
    let collapsed = false;

    try {
      collapsed = window.localStorage.getItem('mds.dashboard.gauges.collapsed.' + boardId) === '1';
    } catch (error) {
      collapsed = false;
    }

    setDashboardGaugeSection(toggle, collapsed);
    toggle.addEventListener('click', function () {
      const shouldCollapse = toggle.getAttribute('aria-expanded') === 'true';
      setDashboardGaugeSection(toggle, shouldCollapse);
      try {
        window.localStorage.setItem('mds.dashboard.gauges.collapsed.' + boardId, shouldCollapse ? '1' : '0');
      } catch (error) {
        // The toggle remains functional when browser storage is unavailable.
      }
    });
  });
}

function setDashboardGaugeSection(toggle, collapsed) {
  const targetId = toggle.getAttribute('aria-controls');
  const gaugeSection = targetId ? document.getElementById(targetId) : null;
  if (!gaugeSection) {
    return;
  }

  const boardCard = toggle.closest('.dashboard-board-card');
  const label = collapsed ? toggle.dataset.showLabel : toggle.dataset.hideLabel;
  const icon = toggle.querySelector('i');
  const labelNode = toggle.querySelector('span');

  gaugeSection.hidden = collapsed;
  toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  toggle.title = label || '';
  if (boardCard) {
    boardCard.classList.toggle('is-gauges-collapsed', collapsed);
  }
  if (labelNode) {
    labelNode.textContent = label || '';
  }
  if (icon) {
    icon.classList.toggle('bi-chevron-down', collapsed);
    icon.classList.toggle('bi-chevron-up', !collapsed);
  }
}

function toggleDashboardOnlineOnly(onlineOnly) {
  document.querySelectorAll('[data-dashboard-board-id]').forEach(function (boardCard) {
    const isOnline = boardCard.dataset.dashboardOnline === '1';
    boardCard.classList.toggle('dashboard-board-hidden', onlineOnly && !isOnline);
  });
}
