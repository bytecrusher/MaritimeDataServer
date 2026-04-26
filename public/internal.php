<?php
  /*
  * File for Display Data for the user
  *
  */
  // Note: add the option to define virtual sensor groups for visual grouping.

  session_start();
  require_once dirname(__DIR__) . "/bootstrap/app.php";
  require_once dirname(__DIR__) . "/app/Infrastructure/Database/dbConfig.func.php";
  require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
  require_once dirname(__DIR__) . "/app/Application/InternalPageService.php";
  require_once dirname(__DIR__) . "/app/Domain/User/user.class.php";
  require_once dirname(__DIR__) . "/app/Domain/Board/board.class.php";
  require_once dirname(__DIR__) . "/app/Infrastructure/Logging/writeToLogFunction.func.php";

  $currentUser = InternalPageService::resolveCurrentUserFromSession();

  if (!$currentUser) {
    header("Location: ./index.php");    // if user not logged in
    die();
  }

  require_once dirname(__DIR__) . "/app/Domain/Board/get_data.php";
  $config = new configuration();
  $pageData = InternalPageService::buildPageData($currentUser, $config);
  $myBoardsIdList = $pageData['myBoardsIdList'];
  $boardObjsArray = $pageData['boardObjsArray'];
  $mapBoardNames = $pageData['mapPayload']['boardNames'];
  $mapGpsData = $pageData['mapPayload']['gpsData'];
  $eventChartSensors = $pageData['eventPayload']['chartSensors'];
  $eventTimelineBoards = $pageData['eventPayload']['timelineBoards'];
  $eventTimelineSummaryLabels = $pageData['eventPayload']['summaryLabels'];
  $eventTimelineSummary = $pageData['eventPayload']['summary'];
  $dashboardUpdateIntervalMs = $pageData['dashboardUpdateIntervalMs'];
  $dashboardOnlineOnlyDefault = $pageData['dashboardOnlineOnlyDefault'];
  $preferredChartWindowDays = $pageData['preferredChartWindowDays'];
  $varDemoMode = $pageData['demoMode'];
  $showInstallAlert = $pageData['showInstallAlert'];
  $hasBoards = $pageData['hasBoards'];

  include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php"; // NOSONAR - Legacy Template-Einbindung
?>
<link rel="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/fontawesome.min.css">
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/brands.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/solid.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/brands.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/solid.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/fontawesome.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?php echo htmlspecialchars(mds_asset_path('js/gauge.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars(mds_asset_path('js/dashboard.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars(mds_asset_path('js/app.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>

<style>
  .internal-hero {
    position: relative;
    overflow: hidden;
    padding: 1.45rem 1.35rem;
    margin-bottom: 1rem;
    border-radius: 1.1rem;
    background:
      radial-gradient(circle at top right, rgba(56, 189, 248, 0.16), transparent 28%),
      linear-gradient(135deg, #111827 0%, #1b2535 48%, #273449 100%);
    color: #fff;
    box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.07);
  }
  .internal-hero::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.05), transparent 38%);
    pointer-events: none;
  }
  .internal-hero h1 {
    margin: 0;
    font-size: clamp(1.6rem, 2.5vw, 2.25rem);
    letter-spacing: -0.03em;
    position: relative;
    z-index: 1;
  }
  .internal-hero p {
    max-width: 46rem;
    margin: 0.45rem 0 0;
    color: rgba(255, 255, 255, 0.82);
    position: relative;
    z-index: 1;
  }
  #internalTabs {
    gap: 0.4rem;
    padding: 0.45rem;
    border: 0;
    border-radius: 1rem 1rem 0 0;
    background: linear-gradient(180deg, #e5e7eb 0%, #dbe4ef 100%);
  }
  #internalTabs .nav-link {
    border: 0;
    border-radius: 999px;
    color: #334155;
    font-weight: 600;
    padding: 0.65rem 1rem;
    transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
  }
  #internalTabs .nav-link:hover {
    background: rgba(255, 255, 255, 0.7);
    color: #0f172a;
    transform: translateY(-1px);
  }
  #internalTabs .nav-link.active {
    background: #fff;
    color: #0f172a;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
  }
  .internal-tab-shell {
    border-bottom-left-radius: 1rem;
    border-bottom-right-radius: 1rem;
    padding-bottom: 1rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.06);
    border: 1px solid rgba(15, 23, 42, 0.06);
    border-top: 0;
  }
  .tab-section-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
    padding: 1rem;
  }
  .tab-section-card + .tab-section-card {
    margin-top: 1rem;
  }
  .tab-section-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
  }
  .tab-section-title h3 {
    margin: 0;
    font-size: 1.05rem;
  }
  .tab-section-title p {
    margin: 0.2rem 0 0;
    color: #64748b;
    font-size: 0.9rem;
  }
  .dashboard-shell {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem 0;
  }
  .dashboard-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    justify-content: space-between;
    padding: 0.9rem 1rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: linear-gradient(135deg, #f8fafc 0%, #eef2f7 100%);
  }
  .dashboard-toolbar-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
  }
  .dashboard-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.45rem 0.7rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(15, 23, 42, 0.08);
    font-size: 0.92rem;
  }
  #alert-container {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
    margin-bottom: 1rem;
  }
  #alert-container .mds-alert {
    margin: 0;
  }
  .dashboard-board-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.06);
  }
  .dashboard-board-card.is-offline {
    opacity: 0.86;
  }
  .dashboard-board-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.95) 0%, rgba(255, 255, 255, 1) 100%);
  }
  .dashboard-board-title {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  .dashboard-board-title h3 {
    margin: 0;
    font-size: 1.2rem;
  }
  .dashboard-board-subtitle {
    color: #64748b;
    font-size: 0.92rem;
  }
  .dashboard-board-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
  }
  .dashboard-board-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    color: #475569;
    font-size: 0.9rem;
  }
  .dashboard-board-gauges {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
    gap: 1.15rem;
    padding: 1rem;
  }
  .dashboard-gauge-card {
    position: relative;
    width: auto !important;
    height: auto !important;
    min-height: 290px;
    padding: 1rem;
    border-radius: 1rem;
    border: 1px solid rgba(148, 163, 184, 0.22);
    background: linear-gradient(180deg, #f8fafc 0%, #e8eef6 100%);
    color: #0f172a;
    transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
    display: flex;
    flex-direction: column;
  }
  .dashboard-gauge-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.10);
    border-color: rgba(37, 99, 235, 0.20);
  }
  .dashboard-gauge-card.disabled {
    opacity: 0.72;
  }
  .dashboard-gauge-top {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.9rem;
    margin-bottom: 0.9rem;
  }
  .dashboard-gauge-headline {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    min-width: 0;
  }
  .dashboard-gauge-headline strong {
    font-size: 1.25rem;
    line-height: 1.2;
    overflow-wrap: anywhere;
  }
  .dashboard-gauge-headline span {
    color: #475569;
    font-size: 0.96rem;
    line-height: 1.2;
    overflow-wrap: anywhere;
  }
  .dashboard-gauge-value {
    text-align: left;
    font-weight: 700;
    font-size: 1rem;
    line-height: 1.15;
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: baseline;
  }
  .dashboard-gauge-value-number {
    font-size: 2rem;
    letter-spacing: -0.02em;
  }
  .dashboard-gauge-value-unit {
    font-size: 1.05rem;
    color: #334155;
    overflow-wrap: anywhere;
  }
  .dashboard-gauge-visual {
    flex: 1 1 auto;
    min-height: 170px;
    height: 170px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 0.2rem;
  }
  .dashboard-gauge-card .gauge,
  .dashboard-gauge-card svg.gauge {
    width: 100%;
    height: 100%;
    max-width: 220px;
    max-height: 170px;
    margin: 0 auto;
    overflow: visible;
  }
  .dashboard-gauge-card.dashboard-gauge-style-minimal .gauge .dial {
    stroke-width: 5;
    stroke: rgba(148, 163, 184, 0.45);
  }
  .dashboard-gauge-card.dashboard-gauge-style-minimal .gauge .value {
    stroke-width: 7;
  }
  .dashboard-gauge-card.dashboard-gauge-style-bold .gauge .dial {
    stroke-width: 9;
    stroke: rgba(148, 163, 184, 0.35);
  }
  .dashboard-gauge-card.dashboard-gauge-style-bold .gauge .value {
    stroke-width: 12;
  }
  .dashboard-gauge-card.dashboard-gauge-style-arc .dashboard-gauge-visual,
  .dashboard-gauge-card.dashboard-gauge-style-industrial .dashboard-gauge-visual {
    min-height: 190px;
    height: 190px;
  }
  .dashboard-gauge-card.dashboard-gauge-style-ring .dashboard-gauge-visual,
  .dashboard-gauge-card.dashboard-gauge-style-clock .dashboard-gauge-visual {
    min-height: 220px;
    height: 220px;
    position: relative;
  }
  .dashboard-gauge-card.dashboard-gauge-style-arc .gauge .dial {
    stroke-width: 4;
    stroke: rgba(59, 130, 246, 0.18);
  }
  .dashboard-gauge-card.dashboard-gauge-style-arc .gauge .value {
    stroke-width: 8;
    stroke-linecap: round;
  }
  .dashboard-gauge-card.dashboard-gauge-style-ring .gauge .dial,
  .dashboard-gauge-card.dashboard-gauge-style-clock .gauge .dial {
    stroke-width: 6;
    stroke: rgba(148, 163, 184, 0.28);
  }
  .dashboard-gauge-card.dashboard-gauge-style-ring .gauge .value,
  .dashboard-gauge-card.dashboard-gauge-style-clock .gauge .value {
    stroke-width: 8;
    stroke-linecap: round;
  }
  .dashboard-gauge-card.dashboard-gauge-style-ring .gauge,
  .dashboard-gauge-card.dashboard-gauge-style-clock .gauge {
    max-width: 220px;
    max-height: 220px;
  }
  .dashboard-gauge-card.dashboard-gauge-style-clock .dashboard-gauge-visual::before {
    content: "";
    position: absolute;
    inset: 8px;
    border-radius: 999px;
    background:
      radial-gradient(circle at center, transparent 0 56%, rgba(15, 23, 42, 0.05) 56% 57%, transparent 57% 100%),
      repeating-conic-gradient(from -90deg, rgba(15, 23, 42, 0.22) 0deg 1.5deg, transparent 1.5deg 30deg);
    opacity: 0.55;
    pointer-events: none;
  }
  .dashboard-gauge-card.dashboard-gauge-style-clock .dashboard-gauge-visual::after {
    content: "";
    position: absolute;
    width: 10px;
    height: 10px;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.7);
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    pointer-events: none;
  }
  .dashboard-gauge-card.dashboard-gauge-style-industrial {
    background: linear-gradient(180deg, #e2e8f0 0%, #d7e0eb 100%);
    border-color: rgba(71, 85, 105, 0.24);
  }
  .dashboard-gauge-card.dashboard-gauge-style-industrial .gauge .dial {
    stroke-width: 7;
    stroke: rgba(71, 85, 105, 0.32);
  }
  .dashboard-gauge-card.dashboard-gauge-style-industrial .gauge .value {
    stroke-width: 10;
    stroke-linecap: butt;
  }
  .dashboard-gauge-meta {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.8rem;
    color: #475569;
    font-size: 0.92rem;
  }
  .dashboard-gauge-meta span {
    overflow-wrap: anywhere;
  }
  .dashboard-empty-state {
    padding: 1rem;
    border-radius: 0.9rem;
    background: rgba(241, 245, 249, 0.7);
    color: #64748b;
  }
  .dashboard-board-hidden {
    display: none !important;
  }
  .board-overview-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 0.85rem;
  }
  .board-overview-item {
    display: flex;
    flex-direction: column;
    gap: 0.55rem;
    padding: 1rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
  }
  .board-overview-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
  }
  .board-overview-name {
    font-weight: 700;
    color: #0f172a;
  }
  .board-overview-meta {
    color: #64748b;
    font-size: 0.9rem;
  }
  .chart-panel canvas {
    width: 100% !important;
    max-height: 380px;
  }
  .event-timeline-board {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0.95rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding: 1rem;
  }
  .event-timeline-board + .event-timeline-board {
    margin-top: 0.85rem;
  }
  .event-timeline-head {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.85rem;
  }
  .event-timeline-head strong {
    font-size: 1rem;
  }
  .event-timeline-head span {
    color: #64748b;
    font-size: 0.9rem;
  }
  .event-timeline-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  .event-timeline-item {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr);
    gap: 0.75rem;
    align-items: start;
  }
  .event-timeline-dot {
    width: 0.9rem;
    height: 0.9rem;
    border-radius: 999px;
    margin-top: 0.18rem;
    box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.12);
  }
  .event-timeline-dot.is-wakeup {
    background: #16a34a;
    box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.14);
  }
  .event-timeline-dot.is-standby {
    background: #f59e0b;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.14);
  }
  .event-timeline-dot.is-other {
    background: #64748b;
  }
  .event-timeline-content {
    min-width: 0;
  }
  .event-timeline-title {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    margin-bottom: 0.15rem;
  }
  .event-timeline-title strong {
    font-size: 0.98rem;
  }
  .event-timeline-title time {
    color: #64748b;
    font-size: 0.85rem;
  }
  .event-timeline-meta {
    color: #475569;
    font-size: 0.88rem;
    overflow-wrap: anywhere;
  }
  .event-timeline-empty {
    padding: 0.95rem 1rem;
    border-radius: 0.85rem;
    background: rgba(241, 245, 249, 0.8);
    color: #64748b;
  }
  .chart-panel-surface {
    padding: 1rem 1rem 0.75rem;
    border-radius: 1rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.65);
  }
  .event-summary-chart-shell {
    position: relative;
    min-height: 320px;
    height: 320px;
  }
  .event-summary-chart-shell canvas {
    width: 100% !important;
    height: 100% !important;
  }
  .event-detail-disclosure {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    overflow: hidden;
  }
  .event-detail-disclosure > summary {
    list-style: none;
    cursor: pointer;
    padding: 1rem 1.1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    font-weight: 700;
    color: #0f172a;
  }
  .event-detail-disclosure > summary::-webkit-details-marker {
    display: none;
  }
  .event-detail-disclosure > summary::after {
    content: 'Einblenden';
    font-size: 0.9rem;
    font-weight: 600;
    color: #475569;
  }
  .event-detail-disclosure[open] > summary::after {
    content: 'Ausblenden';
  }
  .event-detail-body {
    padding: 0 1rem 1rem;
  }
  #chart-container-debug {
    max-height: 520px;
    overflow: auto;
  }
  .debug-table-shell {
    overflow: auto;
    max-width: 100%;
    max-height: 60vh;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0.9rem;
    background: #fff;
  }
  .debug-table-shell table {
    min-width: 1100px;
  }
  .debug-table-shell thead th {
    position: sticky;
    top: 0;
    z-index: 1;
    background: #f8fafc;
    box-shadow: inset 0 -1px 0 rgba(15, 23, 42, 0.08);
    white-space: nowrap;
  }
  .debug-table-shell tbody td {
    white-space: nowrap;
    vertical-align: top;
  }
  #mapContainer .map-shell {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #fff;
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.05);
    padding: 1rem;
  }
  @media (max-width: 767.98px) {
    .dashboard-board-gauges {
      grid-template-columns: 1fr;
    }
    .dashboard-toolbar {
      align-items: flex-start;
    }
    #internalTabs {
      border-radius: 1rem;
    }
  }
</style>

<script>
  $(document).ready(function() {
    updateGauges();
  });
  let gaugesArrayHelper = new Array();
  var SensorArrayHelper = new Array();
  var gaugesMap = new Map();
  var gaugesArrayHelperBig = new Array();

  function checkSession() {
    $.ajax({
      method: "POST",
      url: "api/checkSession.php",
      data: { }
    })
    .done(function( response ) {
      text = response;
      if (text == "false") {
        window.location.href = "./index.php";
      }
    });
  }

  function updateGauges() {
    checkSession();
    var varIdent = getCookie("identifier");
    var varToken = getCookie("securityToken");
    var varData = "sensor";
    var varNrOfValues = "1";
    let text;
    var obj;
    const sensorIdsToRefresh = Array.from(new Set(
      gaugesArrayHelper.map(function (gaugeKey) {
        return String(gaugeKey).split(".")[0];
      })
    ));

    sensorIdsToRefresh.forEach(function (varSensorId) {

      $.ajax({
        method: "POST",
        url: "api/getdata.php",
        dataType: "json",
        data: { identifier: varIdent, securityToken: varToken, data: varData, sensorId: varSensorId, NrOfValues: varNrOfValues }
      })
      .done(function( response ) {
        text = response;
        if (response === '.' || response === null || response === undefined) {
          return;
        }

        if (Array.isArray(response)) {
          obj = response;
        } else if (typeof response === 'string') {
          try {
            obj = JSON.parse(response);
          } catch (error) {
            console.error("invalid gauge response for sensor " + varSensorId, error, response);
            return;
          }
        } else {
          obj = response;
        }

        if (Array.isArray(obj) && obj.length === 1 && obj[0] === '.') {
          return;
        }

        if (!Array.isArray(obj) || obj.length < 2) {
          if (obj !== '.') {
            console.error("unexpected gauge payload for sensor " + varSensorId, obj);
          }
          return;
        }

        for (let i4 = 1; i4 < obj.length; i4++) {
          try {
            //console.error("obj[0]+i4:" + obj[0]+"."+i4 + ", " + gaugesArrayHelper.includes(obj[0]+"."+i4));
            if (gaugesArrayHelper.includes(obj[0]+"."+i4)) {
              const numericValue = Number.parseFloat(obj[i4]);
              if (Number.isFinite(numericValue)) {
                gaugesMap.get(obj[0]+"."+i4).setValueAnimated(numericValue);
                const gaugeCard = document.getElementById("gauge" + obj[0] + "." + i4);
                if (gaugeCard) {
                  const valueNode = gaugeCard.querySelector('.dashboard-gauge-value-number');
                  if (valueNode) {
                    valueNode.textContent = numericValue.toFixed(2);
                  }
                }
              } else {
                console.warn("non-numeric gauge value for sensor " + obj[0] + "." + i4, obj[i4]);
              }
            }
          } catch (error) {
            console.error("error accessing: " + obj[0]+"."+i4);
          }
        }
      })
      .fail(function(jqxhr, settings, ex) {
        console.error('failed (updateGauges), ' + varSensorId + ", " + ex);
      });
    });
  }

  var DashboardUpdateInterval = <?php echo $dashboardUpdateIntervalMs; ?>;
  window.preferredChartWindowDays = <?php echo (int)$preferredChartWindowDays; ?>;
  setInterval(function() {
    // run every 30 seconds
    updateGauges();
  }, DashboardUpdateInterval);
</script>

  <div class="main-container">
  <div class="internal-hero">
      <h1>Welcome <?php echo htmlspecialchars($currentUser->getFirstName(), ENT_QUOTES, 'UTF-8'); ?>
      <?php
      if (configuration::$demoMode) {
        echo htmlspecialchars("  (Demo mode)", ENT_QUOTES, 'UTF-8');
      }
      ?></h1>
      <p>Live-Uebersicht fuer Devices, Sensoren und eingehende Telemetrie.</p>
  </div>
  <div class="container" style="padding: 0px">
    <div id="alert-container">
      <?php
        if($showInstallAlert) {
          echo "<div class='mds-alert alert-dismissible' role='alert'><div><strong>Deployment Hinweis</strong><p>Please remember to remove the \"install\" directory before production use.</p></div><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
        }

        if (!$hasBoards) {
          echo "<div class='mds-alert' role='alert'><div><strong>No board added</strong><p>Please add a board first so dashboard, charts and map can display data.</p></div></div>";
        }
        ?>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="internalTabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#dashboard" role="tab">Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#charts" role="tab">Charts</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#boards" role="tab">Boards</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="hrefmap" data-bs-toggle="tab" href="#mapContainer" role="tab">Map</a>
      </li>
      <?php
        if($currentUser->getUserGroupAdmin() == 1 ) {
      ?>
        <li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#debug' role='tab'>Debug</a></li>
        <?php
        }
        ?>
    </ul>

    <div class="tab-content internal-tab-shell">

      <!-- Show dashboard -->
      <div class="container tab-pane fade show active position-relative" id="dashboard">
        <div class="position-absolute" id="click_lockUnlock" style="top: -40px; right: 0px;" data-bs-toggle="collapse" data-bs-target=".multi-collapse" aria-expanded="false">
          <i class="bi bi-lock-fill" style="font-size:20px; color: #007bff"></i>
        </div>

        <div class="dashboard-shell">
          <div class="dashboard-toolbar">
            <div class="dashboard-toolbar-meta">
              <span class="dashboard-stat-pill"><strong><?php echo count($boardObjsArray); ?></strong> Devices</span>
              <span class="dashboard-stat-pill"><strong><?php echo (int)$dashboardUpdateIntervalMs / 1000; ?>s</strong> Refresh</span>
            </div>
            <div class="dashboard-toolbar-meta">
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="dashboard-online-only-toggle" <?php if ((int)$dashboardOnlineOnlyDefault === 1) { echo 'checked'; } ?>>
                <label class="form-check-label" for="dashboard-online-only-toggle">Nur Online-Devices</label>
              </div>
            </div>
          </div>
          <div class="page-content page-container" id="page-content" style="--bs-gutter-x: 0rem; "></div>
          <div class="container" style="--bs-gutter-x: 0; padding-right: 0px; padding-left: 0px;">
            <?php
            foreach($boardObjsArray as $singleRowmyboard) {
              if($singleRowmyboard->isOnDashboard() == 1) {
                $deviceOnline = checkDeviceIsOnline($singleRowmyboard->getId());
                $mySensors2 = myFunctions::getAllSensorsOfBoardWithDashboardWithTypeName($singleRowmyboard->getId());
                $boardGaugeCount = 0;
                ?>
                  <section class="dashboard-board-card <?php if(!$deviceOnline) { echo 'is-offline'; } ?> <?php if ((int)$dashboardOnlineOnlyDefault === 1 && !$deviceOnline) { echo 'dashboard-board-hidden'; } ?>" data-dashboard-board-id="<?php echo $singleRowmyboard->getId(); ?>" data-dashboard-online="<?php echo $deviceOnline ? '1' : '0'; ?>">
                    <div class="dashboard-board-header">
                      <div class="dashboard-board-title">
                        <h3><?php echo htmlspecialchars($singleRowmyboard->getName(), ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="dashboard-board-subtitle"><?php echo htmlspecialchars($singleRowmyboard->getMacAddress(), ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="dashboard-board-summary">
                          <span>Update alle <?php echo (int)$dashboardUpdateIntervalMs / 1000; ?>s</span>
                          <span>Offline-Timer: <?php echo (int)$singleRowmyboard->getOfflineDataTimer(); ?> min</span>
                        </div>
                      </div>
                      <div class="dashboard-board-badges">
                        <span class="badge <?php echo $deviceOnline ? 'bg-success' : 'bg-danger'; ?>"><?php echo $deviceOnline ? 'Online' : 'Offline'; ?></span>
                        <span class="badge text-bg-light"><?php echo is_array($mySensors2) ? count($mySensors2) : 0; ?> Sensoren</span>
                      </div>
                    </div>
                    <div class="card-block dashboard-board-gauges" id="gaugescontainer<?php echo $singleRowmyboard->getId() ?>">
                <?php
                if ($mySensors2 == null) {
                  ?>
                    <div class='dashboard-empty-state'>Dieses Device hat noch keine Dashboard-Sensoren.</div>
                  <?php
                }
                if ($mySensors2 != null) {
                  foreach($mySensors2 as $singleRowMySensors) {
                    $mySensors = myFunctions::getLatestSensorData($singleRowMySensors['id']);
                    foreach($mySensors as $singleRowMySensorsLastTimeSeen) {
                      $sensortype = myFunctions::getSensorType($singleRowMySensors['typId']);
                      $sensConfig = myFunctions::getSensorConfig($singleRowMySensors['id']);
                      for ($i = 1; $i <= $sensConfig['NrOfUsedSensors']; $i++) {
                        $SensorChannelConfigSingle = myFunctions::getSensorChannelConfig($singleRowMySensors['id'], $i);
                        $currentChannelValue = $singleRowMySensorsLastTimeSeen['value' . $i] ?? null;
                        $numericCurrentChannelValue = is_numeric($currentChannelValue) ? (float)$currentChannelValue : null;
                        $unitValue = html_entity_decode((string)($sensortype['siUnitVal' . $i] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if (($mySensors != null) && (is_array($SensorChannelConfigSingle)) && ($SensorChannelConfigSingle['onDashboard'] == 1) && ($numericCurrentChannelValue !== null)) {
                          $boardGaugeCount++;
                          ?>
                          <div
                            id='gauge<?php echo $singleRowMySensors['id'] . "." . $i; ?>'
                            data-id="<?php echo $SensorChannelConfigSingle['DashboardOrderNr']; ?>"
                            class='ui-state-default dashboard-gauge-card gauge-container <?php if(!$deviceOnline) { echo "disabled"; } ?>'
                            data-gauge-key="<?php echo $singleRowMySensors['id'] . "." . $i; ?>"
                            data-sensor-id="<?php echo $singleRowMySensors['id']; ?>"
                            data-typ-id="<?php echo $singleRowMySensors['typId']; ?>"
                            data-typename="<?php echo htmlspecialchars($singleRowMySensors['typename'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-nr-of-sensors="<?php echo $singleRowMySensors['NrOfUsedSensors']; ?>"
                            data-channel-nr="<?php echo $SensorChannelConfigSingle['channelNr']; ?>"
                            data-sensor-display-name="<?php echo htmlspecialchars($singleRowMySensors['name'] . "." . $SensorChannelConfigSingle['name'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-chart-color="<?php echo htmlspecialchars($SensorChannelConfigSingle['ChartColor'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-board-id="<?php echo $singleRowmyboard->getId(); ?>"
                            data-board-name="<?php echo htmlspecialchars($singleRowmyboard->getName(), ENT_QUOTES, 'UTF-8'); ?>"
                            data-min="<?php echo $SensorChannelConfigSingle['GaugeMinValue']; ?>"
                            data-max="<?php echo $SensorChannelConfigSingle['GaugeMaxValue']; ?>"
                            data-value="<?php echo htmlspecialchars((string)$numericCurrentChannelValue, ENT_QUOTES, 'UTF-8'); ?>"
                            data-low-threshold="<?php echo $SensorChannelConfigSingle['GaugeRedAreaLowValue']; ?>"
                            data-low-color="<?php echo htmlspecialchars($SensorChannelConfigSingle['GaugeRedAreaLowColor'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-high-threshold="<?php echo $SensorChannelConfigSingle['GaugeRedAreaHighValue']; ?>"
                            data-high-color="<?php echo htmlspecialchars($SensorChannelConfigSingle['GaugeRedAreaHighColor'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-normal-color="<?php echo htmlspecialchars($SensorChannelConfigSingle['GaugeNormalAreaColor'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-gauge-style="<?php echo htmlspecialchars($SensorChannelConfigSingle['GaugeStyle'] ?? 'classic', ENT_QUOTES, 'UTF-8'); ?>"
                          >
                            <div class="dashboard-gauge-top">
                              <div class="dashboard-gauge-headline">
                                <strong><?php echo htmlspecialchars($SensorChannelConfigSingle['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?php echo htmlspecialchars($singleRowMySensors['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                              </div>
                              <div class="dashboard-gauge-value">
                                <span class="dashboard-gauge-value-number"><?php echo htmlspecialchars(number_format($numericCurrentChannelValue, 2, '.', ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <span class="dashboard-gauge-value-unit"><?php echo htmlspecialchars($unitValue, ENT_QUOTES, 'UTF-8'); ?></span>
                              </div>
                            </div>
                            <div id='div_click_settings<?php echo $singleRowMySensors['id'] . "." . $i; ?>' class='multi-collapse' style='display:none; z-index: 100; position:absolute; top:12px; right:12px;'>
                              <i id='click_settings<?php echo $singleRowMySensors['id'] . "." . $i; ?>' class='bi bi-gear-fill' data-bs-toggle='modal' data-bs-target='#exampleModal' style='font-size:20px; color: #007bff'></i>
                            </div>
                            <div class="dashboard-gauge-visual"></div>
                            <div class="dashboard-gauge-meta">
                              <span><?php echo htmlspecialchars($singleRowmyboard->getName(), ENT_QUOTES, 'UTF-8'); ?></span>
                              <span><?php echo htmlspecialchars($unitValue, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                          </div>
                          <?php
                        }
                      }
                    }
                  }
                }
                if ($boardGaugeCount === 0) {
                  ?>
                    <div class='dashboard-empty-state'>Aktuell gibt es fuer dieses Device keine numerischen Dashboard-Werte.</div>
                  <?php
                }
                ?>
                    </div>
                  </section>
        <?php
              }
            }
            ?>
          </div>
        </div>
      </div>

      <!-- Show temperatures as chart -->
      <!-- Note: for every board its own canvas. -->
      <div class="container tab-pane fade pl-0 pr-0" id="charts">
        <fieldset class="pt-3">
          <script>
            window.eventChartSensors = <?php echo json_encode($eventChartSensors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            window.eventTimelineSummaryLabels = <?php echo json_encode($eventTimelineSummaryLabels, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            window.eventTimelineSummary = <?php echo json_encode($eventTimelineSummary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
          </script>
          <div id="chart-container">
            <div class="tab-section-card chart-panel">
              <div class="tab-section-title">
                <div>
                  <h3>Temperaturen</h3>
                  <p>Temperaturkurven pro Device vergleichen und gezielt ein- oder ausblenden.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="temperature">Alle anzeigen</button>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="temperature">Alle ausblenden</button>
              </div>
              </div>
              <div id="chart-device-filter-temperature" class="d-flex flex-wrap gap-3 mb-2"></div>
              <canvas id="mycanvas"></canvas>
            </div>
            <div class="tab-section-card chart-panel">
              <div class="tab-section-title">
                <div>
                  <h3>ADC / Spannungen</h3>
                  <p>Spannungen, Pegel und analoge Kanaele pro Device direkt gegeneinander lesen.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="adc">Alle anzeigen</button>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="adc">Alle ausblenden</button>
              </div>
              </div>
              <div id="chart-device-filter-adc" class="d-flex flex-wrap gap-3 mb-2"></div>
              <canvas id="mycanvas2"></canvas>
            </div>
            <div class="tab-section-card chart-panel">
              <div class="tab-section-title">
                <div>
                  <h3>Weitere Sensoren</h3>
                  <p>Alle restlichen Sensortypen in einer gemeinsamen Vergleichsansicht.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="other">Alle anzeigen</button>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="other">Alle ausblenden</button>
              </div>
              </div>
              <div id="chart-device-filter-other" class="d-flex flex-wrap gap-3 mb-2"></div>
              <canvas id="mycanvas3"></canvas>
            </div>
            <div class="tab-section-card chart-panel">
              <div class="tab-section-title">
                <div>
                  <?php $eventWindowTitle = (int)$preferredChartWindowDays === 1 ? 'letzten 24 Stunden' : ('letzten ' . (int)$preferredChartWindowDays . ' Tage'); ?>
                  <h3>ESP Ereignisse</h3>
                  <p>Zeigt den zeitlichen Verlauf der <?php echo htmlspecialchars($eventWindowTitle, ENT_QUOTES, 'UTF-8'); ?>, wie lange ein Device online oder im Standby war.</p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="events">Alle anzeigen</button>
                  <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="events">Alle ausblenden</button>
                </div>
              </div>
              <div id="chart-device-filter-events" class="d-flex flex-wrap gap-3 mb-3"></div>
              <div class="chart-panel-surface mb-4">
                <div class="tab-section-title mb-3">
                  <div>
                    <h4 class="mb-1">Verlauf der <?php echo htmlspecialchars($eventWindowTitle, ENT_QUOTES, 'UTF-8'); ?></h4>
                    <?php $windowLabel = $eventWindowTitle; ?>
                    <p>Pro Tag siehst du die Summe in Stunden, die ein Device online oder im Standby war, fuer die <?php echo htmlspecialchars($windowLabel, ENT_QUOTES, 'UTF-8'); ?>.</p>
                  </div>
                </div>
                <div class="event-summary-chart-shell">
                  <canvas id="eventSummaryCanvas" height="320"></canvas>
                </div>
              </div>
              <details class="event-detail-disclosure mt-4">
                <summary>Detailverlauf</summary>
                <div class="event-detail-body">
                  <p class="text-muted mb-3">Die letzten einzelnen Wakeup- und Standby-Ereignisse pro Device.</p>
                  <div id="event-timeline-container" class="d-flex flex-column gap-3" data-server-rendered="1">
                    <?php if (empty($eventTimelineBoards)) { ?>
                      <div class="event-timeline-empty">Noch keine Wakeup- oder Standby-Ereignisse vorhanden.</div>
                    <?php } else { ?>
                      <?php foreach ($eventTimelineBoards as $eventTimelineBoard) { ?>
                        <section class="event-timeline-board" data-event-board-id="<?php echo (int)$eventTimelineBoard['boardId']; ?>">
                          <div class="event-timeline-head">
                            <div>
                              <strong><?php echo htmlspecialchars($eventTimelineBoard['boardName'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                              <span><?php echo count($eventTimelineBoard['events']); ?> Ereignisse im Verlauf</span>
                            </div>
                          </div>
                          <ol class="event-timeline-list">
                            <?php foreach ($eventTimelineBoard['events'] as $eventTimelineEntry) { ?>
                              <li class="event-timeline-item">
                                <span class="event-timeline-dot <?php echo htmlspecialchars($eventTimelineEntry['stateClass'], ENT_QUOTES, 'UTF-8'); ?>"></span>
                                <div class="event-timeline-content">
                                  <div class="event-timeline-title">
                                    <strong><?php echo htmlspecialchars($eventTimelineEntry['label'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <time><?php echo htmlspecialchars($eventTimelineEntry['timestamp'], ENT_QUOTES, 'UTF-8'); ?></time>
                                  </div>
                                  <div class="event-timeline-meta">
                                    <?php
                                      $detailParts = array();
                                      if (!empty($eventTimelineEntry['sensorName'])) {
                                        $detailParts[] = $eventTimelineEntry['sensorName'];
                                      }
                                      if (!empty($eventTimelineEntry['rawLabel']) && $eventTimelineEntry['rawLabel'] !== $eventTimelineEntry['label']) {
                                        $detailParts[] = 'Rohwert: ' . $eventTimelineEntry['rawLabel'];
                                      }
                                      if (!empty($eventTimelineEntry['fallbackTimestamp']) && $eventTimelineEntry['fallbackTimestamp'] !== $eventTimelineEntry['timestamp']) {
                                        $detailParts[] = 'Datensatz: ' . $eventTimelineEntry['fallbackTimestamp'];
                                      }
                                      echo htmlspecialchars(implode(' · ', $detailParts), ENT_QUOTES, 'UTF-8');
                                    ?>
                                  </div>
                                </div>
                              </li>
                            <?php } ?>
                          </ol>
                        </section>
                      <?php } ?>
                    <?php } ?>
                  </div>
                </div>
              </details>
            </div>
          </div>
        </fieldset>
      </div>

      <!-- Show Board overview -->
      <div class="container tab-pane fade pl-0 pr-0" id="boards">
        <fieldset class="pt-3">
            <div class="tab-section-card">
              <div class="tab-section-title">
                <div>
                  <h3>Boards</h3>
                  <p>Status, Uebertragungsweg und Zuordnung deiner registrierten Devices.</p>
                </div>
              </div>
              <div class="board-overview-list">
            <?php
            foreach($boardObjsArray as $singleBoardObj) {
              $transmissionPath = 0;
              $mySensors2 = myFunctions::getAllSensorsOfBoard($singleBoardObj->getId());
              $boardOnlineStatus = false;
              $mySensorIdList = null;
              if ($mySensors2 == null) {
                ?>
                  <div class='container mt-2'>
                    <span class='badge bg-danger mr-2' style='width: 55px;'>Offline</span>
                    <span class='control-label' style='padding-left: 5px'><?php echo $singleBoardObj->getName(); ?> (<?php echo $singleBoardObj->getMacAddress(); ?>)</span>
                  </div>
                <?php
                continue;
              }
              foreach($mySensors2 as $singleRowMySensors) {
                if ($mySensorIdList == null) {
                  $mySensorIdList = $singleRowMySensors['id'];
                } else {
                  $mySensorIdList = $mySensorIdList . ", " . $singleRowMySensors['id'];
                }
              }
              $mySensors = myFunctions::getLatestSensorData($mySensorIdList);
              foreach($mySensors as $singleRowMySensorsLastTimeSeen) {
                $transmissionPath = $singleRowMySensorsLastTimeSeen['transmissionPath'];
                $dbTimestamp = strtotime($singleRowMySensorsLastTimeSeen['reading_time']);

                // Show Online / Offline
                // if demoMode == true, then no limit.
                if ($varDemoMode) {
                  $maxTimeout = strtotime("-10 Years");
                } else {
                  $maxTimeout = strtotime("-" . $singleBoardObj->getOfflineDataTimer() . " Minutes"); // For show Online / Offline
                }

                if ($dbTimestamp > $maxTimeout) {
                  $deviceIsOnline[$singleBoardObj->getId()] = (bool)true;
                  $boardOnlineStatus = true;
                } else {
                  $deviceIsOnline[$singleBoardObj->getId()] = (bool)false;
                }
              }
            ?>
              <div class='board-overview-item'>
                <?php
                if ($boardOnlineStatus) {
                ?>
                  <div class='board-overview-badges'>
                  <span class='badge bg-success mr-2' style='width: 55px;'>Online</span>
                <?php
                  if ($transmissionPath == 1) {
                    ?>
                      <span class='badge bg-success mr-2' style='width: 55px;'>WiFi</span>
                    <?php
                  } elseif ($transmissionPath == 2) {
                    ?>
                      <span class='badge bg-success mr-2' style='width: 55px;'>Lora</span>
                    <?php
                  } else {
                    // no specific transmission path badge
                  }
                } else {
                ?>
                  <div class='board-overview-badges'>
                  <span class='badge bg-danger mr-2' style='width: 55px;'>Offline</span>
                <?php
                }
                ?>
                  </div>
                  <div class='board-overview-name'><?php echo $singleBoardObj->getName(); ?></div>
                  <div class='board-overview-meta'><?php echo $singleBoardObj->getMacAddress(); ?></div>
              </div>
            <?php
            }
            ?>
              </div>
            </div>
        </fieldset>
      </div>

      <!-- Show temperatures as table, only for admin -->
      <div class="container tab-pane fade pl-0 pr-0" id="debug">
        <div class="tab-section-card mt-3">
          <div class="tab-section-title">
            <div>
              <h3>Debug</h3>
              <p>Rohdaten aus `ttnDataLoraBoatMonitor` fuer Analyse und Fehlersuche.</p>
            </div>
          </div>
          <div class="p-2 d-flex flex-column gap-2" id="chart-container-debug">
            <p class="mb-0 text-muted">Alle TTN-Rohdaten aus `ttnDataLoraBoatMonitor`. Horizontal und vertikal scrollbar.</p>
          </div>
        <?php
            include_once dirname(__DIR__) . "/app/Http/Webhooks/TTN/index.php"; // NOSONAR - Legacy Bootstrap, Autoload nicht verfügbar
          ?>
        </div>
      </div>

      <!-- Show map -->
      <div class="container tab-pane fade pl-0" id="mapContainer">
        <div class="row mt-2">
          <div class="container map-shell">
            <?php include_once __DIR__ . "/openstreetmaps.php"; // NOSONAR - Legacy Template-Einbindung ?>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <!-- definition in "formSensors.php"-->
      </div>
    </div>
  </div>

</div>
    <script>
      $('#click_lockUnlock').click(function() {
        $("i", this).toggleClass("bi bi-lock-fill bi bi-unlock-fill");
        if (($("i", this).attr('class') ) == "bi bi-unlock-fill") {
          $('.gauge-container').css("cursor", "move");
          $(".card-block").sortable();
          $(".card-block").sortable( "option", "disabled", false );
          $(".card-block").disableSelection();
          $('.multi-collapse').toggle();
        } else {
          $('.multi-collapse').toggle();
          $('.gauge-container').css("cursor", "auto");
          $(".card-block").sortable("disable");

          wrapper = $('.card-block');
          onceSensorOrderFail = false;
          onceSensorOrderDone = false;
          for (let i=0; i<wrapper.length; i++) {
            $("#" + wrapper[i].id + " .gauge-container").each(function(index) {
              //console.log("new order nr: " + index + ", old order nr: " + $( this ).attr("data-id"));
              $( this ).attr("data-id", index);
              var tempNumber = $( this ).attr("id").replace('gauge', '');
              var SensorIdChannel = tempNumber.split('.');
              $.ajax({
                method: "POST",
                url: "api/updateData.php",
                data: { update: "sensorOrderNumber",
                    channel: SensorIdChannel[1],
                    orderNumber: $( this ).attr("data-id"),
                    id: SensorIdChannel[0] }
              })
                .done(function( response ) {
                  if (!onceSensorOrderDone) {
                    g = document.createElement('div');
                    g.setAttribute("class", "alert alert-success alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
                    g.setAttribute("role", "alert");
                    g.innerHTML = "Sensor order saved.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                    const bsAlert = new bootstrap.Alert(g);
                    // Dismiss time out
                    setTimeout(() => {
                      bsAlert.close();
                    }, 5000);
                    $("#alert-container").append(g);
                    onceSensorOrderDone = true;
                  }
                })
                .fail(function( response ) {
                  if (!onceSensorOrderFail) {
                    g = document.createElement('div');
                    g.setAttribute("class", "alert alert-danger alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
                    g.setAttribute("role", "alert");
                    g.innerHTML = "Sensor order not saved.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                    const bsAlert = new bootstrap.Alert(g);
                    // Dismiss time out
                    setTimeout(() => {
                      bsAlert.close();
                    }, 5000);
                    $("#alert-container").append(g);
                    onceSensorOrderFail = true;
                  }
                });
            });
          }
        }
      });

      // TODO: Bug!!! on more than one board, the sensors will be added to everyone.
      $( document ).ready(function() {
        function showInternalTab(targetSelector) {
          if (!targetSelector || targetSelector.charAt(0) !== '#') {
            return;
          }

          $('#internalTabs .nav-link').removeClass('active').attr('aria-selected', 'false');
          $('.tab-content .tab-pane').removeClass('active show');

          $('#internalTabs a[href="' + targetSelector + '"]').addClass('active').attr('aria-selected', 'true');
          $(targetSelector).addClass('active show');

          if (targetSelector === '#mapContainer' && typeof initInternalMap === 'function') {
            window.setTimeout(function() {
              initInternalMap();
              if (typeof map !== 'undefined' && map && typeof map.invalidateSize === 'function') {
                window.setTimeout(function() {
                  map.invalidateSize();
                }, 150);
              }
            }, 50);
          }

          if (targetSelector === '#charts' && typeof refreshChartsTabViews === 'function') {
            window.setTimeout(function() {
              refreshChartsTabViews();
            }, 80);
          }

          if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', targetSelector);
          }
        }

        $('#internalTabs').on('click', 'a[data-bs-toggle="tab"]', function(event) {
          event.preventDefault();
          showInternalTab($(this).attr('href'));
        });

        const currentHash = window.location.hash;
        if (currentHash && $('#internalTabs a[href="' + currentHash + '"]').length) {
          showInternalTab(currentHash);
        }

        $('.gauge-container').css("cursor", "auto");
        var wrapper = $('.card-block');
        for (let i=0; i<wrapper.length; i++) {
          var wrapper2 = $('#' + wrapper[i].id);
          wrapper2.find('.gauge-container').sort(function (a, b) {
          return +a.dataset.id - +b.dataset.id;
          }).appendTo( wrapper2 );
        }
      });
      
    (function () {
        $('#exampleModal').on('show.bs.modal', function (e) {
        str = e.relatedTarget.id;
        let newStr = str.replace('click_settings', '');
        const myArray = newStr.split(".");
        $.ajax({
          url: 'formSensors.php?id=' + myArray[0] + '&channel=' + myArray[1] + '&modal=true'
        }).done(function(response) {
          $('.modal-content').html(response);
        });
      });
    })();

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
    </script>
  <?php
    include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php"; // NOSONAR - Legacy Template-Einbindung
  ?>
