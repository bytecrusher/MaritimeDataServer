function updateGaugeStatus(sensorId, response) {
  if (!response || !Array.isArray(response.values) || typeof response.current !== 'boolean') return;
  document.querySelectorAll('[data-sensor-id="' + Number(sensorId) + '"]').forEach(function (card) {
    card.classList.toggle('disabled', !response.boardOnline || !response.current);
    const freshness = card.querySelector('[data-gauge-freshness]');
    if (freshness) {
      const date = new Date(response.lastReceivedAt);
      freshness.textContent = response.freshnessLabel + (response.lastReceivedAt && Number.isFinite(date.getTime()) ? ' · ' + date.toLocaleString() : '');
    }
    const board = card.closest('[data-dashboard-board-id]');
    if (!board) return;
    board.dataset.dashboardOnline = response.boardOnline ? '1' : '0';
    board.classList.toggle('is-offline', !response.boardOnline);
    const onlyOnline = document.getElementById('dashboard-online-only-toggle');
    board.classList.toggle('dashboard-board-hidden', Boolean(onlyOnline && onlyOnline.checked && !response.boardOnline));
    const badge = board.querySelector('[data-board-status]');
    if (badge) {
      badge.textContent = response.boardLabel;
      badge.classList.toggle('bg-success', response.boardOnline);
      badge.classList.toggle('bg-danger', !response.boardOnline);
    }
    const activity = board.querySelector('[data-sensor-activity]');
    if (activity) {
      activity.textContent = response.activityLabel;
      activity.title = response.activityHint;
    }
  });
}
