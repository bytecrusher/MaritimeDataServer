<?php
  /*
  * File for Display Data for the user
  *
  */
  // Note: add the option to define virtual sensor groups for visual grouping.

  require_once dirname(__DIR__) . "/bootstrap/app.php";
  mds_start_session();
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
  $eventTimelineLast24h = $pageData['eventPayload']['last24hTimeline'];
  $eventStatusByBoard = $pageData['eventPayload']['statusByBoard'] ?? array();
  $dashboardUpdateIntervalMs = $pageData['dashboardUpdateIntervalMs'];
  $dashboardOnlineOnlyDefault = $pageData['dashboardOnlineOnlyDefault'];
  $preferredChartWindowDays = $pageData['preferredChartWindowDays'];
  $eventTimelineWindowHours = $pageData['eventTimelineWindowHours'];
  $varDemoMode = $pageData['demoMode'];
  $showInstallAlert = $pageData['showInstallAlert'];
  $hasBoards = $pageData['hasBoards'];

  if (!function_exists('mds_format_internal_duration')) {
    function mds_format_internal_duration($seconds)
    {
      $seconds = max(0, (int)$seconds);
      if ($seconds > 0 && $seconds < 60) {
        return '< 1 min';
      }

      $minutes = (int)round($seconds / 60);
      $days = intdiv($minutes, 1440);
      $minutes -= $days * 1440;
      $hours = intdiv($minutes, 60);
      $minutes -= $hours * 60;

      $parts = array();
      if ($days > 0) {
        $parts[] = $days . ' d';
      }
      if ($hours > 0) {
        $parts[] = $hours . ' h';
      }
      if ($minutes > 0 || empty($parts)) {
        $parts[] = $minutes . ' min';
      }

      return implode(' ', $parts);
    }
  }

  include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php"; // NOSONAR - shared template include
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/fontawesome.min.css">
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
  .dashboard-board-badges .badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.5rem 0.75rem;
    font-weight: 600;
  }
  .dashboard-board-badges .badge.mds-standby-badge {
    border: 1px solid rgba(59, 130, 246, 0.16);
    background: rgba(59, 130, 246, 0.10);
    color: #1d4ed8;
  }
  .dashboard-board-badges .badge.mds-persistent-online-badge {
    border: 1px solid rgba(14, 165, 233, 0.18);
    background: rgba(14, 165, 233, 0.12);
    color: #0f766e;
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
    height: 100% !important;
  }
  .chart-scroll-shell {
    position: relative;
    width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    border-radius: 1rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    -webkit-overflow-scrolling: touch;
  }
  .chart-scroll-inner {
    min-width: 760px;
    height: 380px;
    padding: 0.85rem 0.95rem 0.75rem;
  }
  .chart-mobile-hint {
    display: none;
    margin: 0.35rem 0 0;
    color: #64748b;
    font-size: 0.82rem;
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
  .event-timeline-head-main {
    min-width: 0;
  }
  .event-timeline-head strong {
    font-size: 1rem;
  }
  .event-timeline-head span {
    color: #64748b;
    font-size: 0.9rem;
  }
  .event-timeline-status {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.45rem;
  }
  .event-timeline-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.7rem;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.24);
    background: #f8fafc;
    color: #334155;
    font-size: 0.88rem;
    font-weight: 600;
  }
  .event-timeline-status-pill.is-persistent-online {
    border-color: rgba(14, 165, 233, 0.18);
    background: rgba(14, 165, 233, 0.12);
    color: #0f766e;
  }
  .event-timeline-status-pill.is-standby-enabled {
    border-color: rgba(59, 130, 246, 0.18);
    background: rgba(59, 130, 246, 0.10);
    color: #1d4ed8;
  }
  .event-timeline-status-pill.is-wakeup-cycle {
    border-color: rgba(59, 130, 246, 0.18);
    background: rgba(59, 130, 246, 0.10);
    color: #1d4ed8;
  }
  .event-timeline-status-pill.is-wakeup {
    border-color: rgba(34, 197, 94, 0.18);
    background: rgba(34, 197, 94, 0.12);
    color: #15803d;
  }
  .event-timeline-status-pill.is-standby {
    border-color: rgba(245, 158, 11, 0.18);
    background: rgba(245, 158, 11, 0.12);
    color: #b45309;
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
  .event-window-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.85rem;
    margin-bottom: 1rem;
  }
  .event-window-control {
    min-width: 12rem;
    padding: 0.65rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0.85rem;
    background: rgba(248, 250, 252, 0.88);
  }
  .event-window-control label {
    display: block;
    margin-bottom: 0.35rem;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }
  .event-window-card {
    padding: 0.9rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0.9rem;
    background:
      radial-gradient(circle at top right, rgba(14, 165, 233, 0.12), transparent 34%),
      linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }
  .event-window-card h5 {
    margin: 0 0 0.65rem;
    font-size: 0.95rem;
    font-weight: 800;
    color: #0f172a;
  }
  .event-window-metrics {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
  }
  .event-window-metric {
    flex: 1 1 7rem;
    padding: 0.65rem;
    border-radius: 0.75rem;
    background: rgba(241, 245, 249, 0.82);
  }
  .event-window-metric strong {
    display: block;
    font-size: 1.1rem;
    color: #0f172a;
  }
  .event-window-metric span {
    color: #64748b;
    font-size: 0.82rem;
  }
  .event-window-bar {
    display: flex;
    height: 0.55rem;
    margin-top: 0.75rem;
    overflow: hidden;
    border-radius: 999px;
    background: #e2e8f0;
  }
  .event-window-bar-online {
    background: #16a34a;
  }
  .event-window-bar-standby {
    background: #f59e0b;
  }
  .event-chart-legend {
    display: flex;
    flex-wrap: wrap;
    gap: 0.65rem;
    align-items: center;
    margin: 0 0 0.75rem;
    color: #475569;
    font-size: 0.88rem;
    font-weight: 600;
  }
  .event-chart-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }
  .event-chart-legend-dot {
    width: 0.7rem;
    height: 0.7rem;
    border-radius: 999px;
  }
  .event-chart-legend-dot.is-online {
    background: #16a34a;
  }
  .event-chart-legend-dot.is-standby {
    background: #f59e0b;
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
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
  }
  .event-chart-scroll-inner {
    min-width: 680px;
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
  @media (max-width: 767.98px) {
    .event-timeline-status {
      justify-content: flex-start;
    }
    .chart-panel {
      padding: 1rem 0.75rem;
    }
    .chart-panel .tab-section-title {
      align-items: flex-start;
      gap: 0.85rem;
    }
    .chart-panel .tab-section-title h3 {
      font-size: 1.1rem;
    }
    .chart-panel .tab-section-title p {
      font-size: 0.9rem;
      line-height: 1.35;
    }
    .chart-device-filter {
      gap: 0.45rem !important;
      flex-wrap: nowrap !important;
      overflow-x: auto;
      padding-bottom: 0.35rem;
      -webkit-overflow-scrolling: touch;
    }
    .chart-device-filter > * {
      flex: 0 0 auto;
    }
    .chart-panel + .chart-panel {
      margin-top: 0.7rem;
    }
    .chart-panel .tab-section-title p {
      display: none;
    }
    .chart-panel-surface {
      padding: 0.75rem;
    }
    .chart-panel-surface.mb-4 {
      margin-bottom: 0.8rem !important;
    }
    .chart-scroll-shell,
    .event-summary-chart-shell {
      overflow-x: hidden;
    }
    .chart-scroll-inner {
      width: 100%;
      min-width: 0;
      height: 280px;
      padding: 0.55rem 0.45rem 0.45rem;
    }
    .chart-mobile-hint {
      display: none;
    }
    .event-chart-scroll-inner {
      width: 100%;
      min-width: 0;
      height: 280px;
    }
    .event-window-summary {
      display: flex;
      gap: 0.65rem;
      overflow-x: auto;
      padding-bottom: 0.35rem;
      scroll-snap-type: x proximity;
      -webkit-overflow-scrolling: touch;
    }
    .event-window-card {
      flex: 0 0 min(82vw, 280px);
      scroll-snap-align: start;
    }
    .event-window-control {
      width: 100%;
      min-width: 0;
    }
    .event-chart-legend {
      margin-bottom: 0.45rem;
    }
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

  /* Telemetry workspace refresh */
  .mds-page-internal .main-container {
    width: min(1200px, calc(100% - 24px));
  }
  .mds-page-internal .main-container > .container {
    width: 100%;
    max-width: none;
  }
  .internal-hero {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: end;
    gap: 1.25rem;
    min-height: 150px;
    padding: clamp(1.2rem, 2.5vw, 1.75rem);
    margin-bottom: 0.8rem;
    border-radius: 1.75rem;
    background:
      linear-gradient(rgba(125, 211, 252, 0.05) 1px, transparent 1px),
      linear-gradient(90deg, rgba(125, 211, 252, 0.05) 1px, transparent 1px),
      radial-gradient(circle at 86% 18%, rgba(34, 211, 238, 0.22), transparent 22rem),
      linear-gradient(135deg, #071a31 0%, #102d47 58%, #12445a 100%);
    background-size: 36px 36px, 36px 36px, auto, auto;
    box-shadow: 0 28px 70px rgba(15, 23, 42, 0.18);
  }
  .internal-hero::before {
    content: "";
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 5px;
    background: linear-gradient(180deg, #34d399, #22d3ee);
  }
  .internal-hero-copy,
  .internal-hero-status {
    position: relative;
    z-index: 1;
  }
  .internal-hero-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.45rem;
    color: #86efac;
    font-size: 0.74rem;
    font-weight: 800;
    letter-spacing: 0.13em;
    text-transform: uppercase;
  }
  .internal-hero-eyebrow::before {
    content: "";
    width: 0.5rem;
    height: 0.5rem;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 0 0.32rem rgba(52, 211, 153, 0.12);
  }
  .mds-page-internal .internal-hero h1 {
    max-width: 780px;
    color: #f8fafc !important;
    font-size: clamp(1.85rem, 3vw, 2.65rem);
    line-height: 1.02;
    text-shadow: 0 2px 20px rgba(2, 8, 23, 0.28);
  }
  .internal-hero p {
    max-width: 680px;
    margin-top: 0.5rem;
    color: rgba(226, 232, 240, 0.86);
    font-size: 1rem;
    line-height: 1.45;
  }
  .internal-hero-status {
    display: grid;
    grid-template-columns: minmax(140px, 1fr);
    gap: 0.65rem;
  }
  .internal-hero-stat {
    min-width: 120px;
    padding: 0.75rem 0.9rem;
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 1rem;
    background: rgba(3, 16, 35, 0.44);
    backdrop-filter: blur(12px);
  }
  .internal-hero-stat strong,
  .internal-hero-stat span {
    display: block;
  }
  .internal-hero-stat strong {
    color: #fff;
    font-size: 1.45rem;
    line-height: 1;
  }
  .internal-hero-stat span {
    margin-top: 0.4rem;
    color: rgba(226, 232, 240, 0.72);
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }
  .internal-tabs-bar {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.55rem;
    border: 1px solid rgba(15, 23, 42, 0.09);
    border-radius: 1.25rem;
    background: rgba(255, 255, 255, 0.82);
    box-shadow: 0 15px 35px rgba(15, 23, 42, 0.07);
    backdrop-filter: blur(16px);
  }
  #internalTabs {
    flex: 1 1 auto;
    min-width: 0;
    gap: 0.3rem;
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
  }
  #internalTabs .nav-link {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.72rem 1rem;
    border-radius: 0.85rem;
  }
  #internalTabs .nav-link i {
    color: #64748b;
    font-size: 1rem;
  }
  #internalTabs .nav-link.active {
    color: #fff;
    background: linear-gradient(135deg, #0f766e, #087a99);
    box-shadow: 0 10px 24px rgba(8, 122, 153, 0.22);
  }
  #internalTabs .nav-link.active i {
    color: #a7f3d0;
  }
  body.mds-app-shell.mds-page-internal .internal-tab-shell {
    padding: 0;
    border: 0;
    border-radius: 0;
    background: transparent;
    box-shadow: none;
  }
  .internal-tab-shell > .tab-pane {
    max-width: none;
    padding: 1.25rem 0 0;
  }
  .dashboard-shell {
    gap: 1.25rem;
    padding: 0;
  }
  .dashboard-tab-tools {
    display: flex;
    flex: 0 0 auto;
    align-items: center;
    gap: 0.55rem;
  }
  .dashboard-gauge-size-picker {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    min-height: 2.7rem;
    padding: 0.25rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0.8rem;
    background: #fff;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
  }
  .dashboard-gauge-size-picker-label {
    padding: 0 0.45rem 0 0.55rem;
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 750;
    white-space: nowrap;
  }
  .dashboard-gauge-size-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.15rem;
    height: 2.15rem;
    padding: 0;
    border: 0;
    border-radius: 0.6rem;
    background: transparent;
    color: #64748b;
    font-size: 0.78rem;
    font-weight: 850;
    transition: background-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
  }
  .dashboard-gauge-size-button:hover,
  .dashboard-gauge-size-button:focus-visible {
    color: #087a99;
    background: #eef7f8;
  }
  .dashboard-gauge-size-button.is-active {
    color: #fff;
    background: linear-gradient(135deg, #0f766e, #087a99);
    box-shadow: 0 6px 14px rgba(8, 122, 153, 0.22);
  }
  .dashboard-tab-tools[hidden] {
    display: none !important;
  }
  .dashboard-stat-pill {
    min-height: 2.7rem;
    padding: 0.55rem 0.8rem;
    border-radius: 0.8rem;
    background: #fff;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.05);
  }
  .dashboard-tab-tools .form-switch {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    min-height: 2.7rem;
    padding: 0.45rem 0.8rem 0.45rem 2.8rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0.8rem;
    background: #fff;
  }
  .dashboard-layout-button {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    min-height: 2.7rem;
    padding: 0.5rem 0.8rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 0.8rem;
    background: #fff;
    color: #334155;
    font-weight: 700;
  }
  .dashboard-layout-button:hover,
  .dashboard-layout-button:focus-visible {
    border-color: rgba(8, 122, 153, 0.25);
    color: #087a99;
  }
  .dashboard-layout-button i {
    color: #087a99 !important;
    font-size: 1rem !important;
  }
  .dashboard-board-card {
    position: relative;
    border-radius: 1.35rem;
    border-color: rgba(15, 23, 42, 0.1);
    background: rgba(255, 255, 255, 0.94);
    box-shadow: 0 20px 50px rgba(15, 23, 42, 0.08);
  }
  .dashboard-board-card::before {
    content: "";
    position: absolute;
    z-index: 2;
    top: 0;
    bottom: 0;
    left: 0;
    width: 4px;
    background: linear-gradient(180deg, #34d399, #0f766e);
  }
  .dashboard-board-card.is-offline::before {
    background: linear-gradient(180deg, #fb7185, #be123c);
  }
  .dashboard-board-card.is-offline {
    opacity: 1;
  }
  .dashboard-board-header {
    padding: 1.25rem 1.35rem;
    background:
      radial-gradient(circle at 90% 10%, rgba(34, 167, 196, 0.08), transparent 18rem),
      linear-gradient(180deg, #fff 0%, #f8fafc 100%);
  }
  .dashboard-board-title h3 {
    font-size: 1.35rem;
  }
  .dashboard-board-summary {
    margin-top: 0.3rem;
  }
  .dashboard-board-summary span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: #64748b;
    font-size: 0.82rem;
    font-weight: 650;
  }
  .dashboard-board-badges .badge {
    border-radius: 999px;
  }
  .dashboard-board-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.4rem;
    min-height: 2.25rem;
    padding: 0.4rem 0.7rem;
    border: 1px solid rgba(15, 23, 42, 0.1);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.9);
    color: #334155;
    font-size: 0.78rem;
    font-weight: 750;
    transition: border-color 160ms ease, color 160ms ease, background-color 160ms ease;
  }
  .dashboard-board-toggle:hover,
  .dashboard-board-toggle:focus-visible {
    border-color: rgba(8, 122, 153, 0.35);
    background: #fff;
    color: #087a99;
  }
  .dashboard-board-toggle i {
    transition: transform 180ms ease;
  }
  .dashboard-board-card.is-gauges-collapsed .dashboard-board-header {
    border-bottom-color: transparent;
  }
  .dashboard-board-gauges[hidden] {
    display: none !important;
  }
  .dashboard-board-gauges {
    grid-template-columns: repeat(auto-fit, minmax(245px, 1fr));
    gap: 0.9rem;
    padding: 1rem;
    background:
      linear-gradient(rgba(15, 118, 110, 0.025) 1px, transparent 1px),
      linear-gradient(90deg, rgba(15, 118, 110, 0.025) 1px, transparent 1px),
      #f8fafc;
    background-size: 28px 28px, 28px 28px, auto;
  }
  .dashboard-gauge-card {
    min-height: 275px;
    padding: 1.1rem;
    overflow: hidden;
    border-radius: 1.1rem;
    border-color: rgba(15, 23, 42, 0.1);
    background:
      radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--gauge-accent, #22a7c4) 11%, transparent), transparent 9rem),
      linear-gradient(160deg, #fff 0%, #f3f7fa 100%);
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
  }
  .dashboard-gauge-card::before {
    content: "";
    position: absolute;
    top: 0;
    right: 1.1rem;
    left: 1.1rem;
    height: 3px;
    border-radius: 0 0 999px 999px;
    background: var(--gauge-accent, #22a7c4);
    opacity: 0.82;
  }
  .dashboard-gauge-card:hover {
    transform: translateY(-3px);
    border-color: color-mix(in srgb, var(--gauge-accent, #22a7c4) 35%, rgba(15, 23, 42, 0.1));
    box-shadow: 0 18px 38px rgba(15, 23, 42, 0.09);
  }
  .dashboard-gauge-top {
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: start;
    gap: 0.75rem;
    margin-bottom: 0.35rem;
  }
  .dashboard-gauge-headline strong {
    color: #0b2038;
    font-size: 1.05rem;
  }
  .dashboard-gauge-headline span {
    color: #64748b;
    font-size: 0.8rem;
    font-weight: 650;
    letter-spacing: 0.035em;
    text-transform: uppercase;
  }
  .dashboard-gauge-value {
    justify-content: flex-end;
    text-align: right;
  }
  .dashboard-gauge-value-number {
    color: #071a31;
    font-size: clamp(1.65rem, 3vw, 2.15rem);
    font-variant-numeric: tabular-nums;
  }
  .dashboard-gauge-value-unit {
    color: #475569;
    font-size: 0.9rem;
  }
  .dashboard-gauge-visual {
    min-height: 150px;
    height: 150px;
    margin-top: 0;
  }
  .dashboard-gauge-card .gauge,
  .dashboard-gauge-card svg.gauge {
    max-width: 230px;
    max-height: 155px;
  }
  .dashboard-gauge-card .gauge .dial {
    stroke: #dbe6ee;
  }
  .dashboard-gauge-card .gauge .value {
    filter: drop-shadow(0 2px 3px rgba(15, 23, 42, 0.12));
    stroke-linecap: round;
  }
  .dashboard-gauge-scale {
    display: flex;
    justify-content: space-between;
    gap: 0.75rem;
    margin-top: 0.15rem;
    padding-top: 0.65rem;
    border-top: 1px solid rgba(15, 23, 42, 0.07);
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
  }
  .dashboard-gauge-scale span {
    display: grid;
    gap: 0.15rem;
  }
  .dashboard-gauge-scale span:last-child {
    text-align: right;
  }
  .dashboard-gauge-scale small {
    color: #94a3b8;
    font-size: 0.62rem;
    font-weight: 800;
    letter-spacing: 0.09em;
    line-height: 1;
    text-transform: uppercase;
  }
  #dashboard[data-gauge-size="small"] .dashboard-board-gauges {
    grid-template-columns: repeat(auto-fit, minmax(205px, 1fr));
    gap: 0.7rem;
  }
  #dashboard[data-gauge-size="small"] .dashboard-gauge-card {
    min-height: 230px;
    padding: 0.9rem;
  }
  #dashboard[data-gauge-size="small"] .dashboard-gauge-headline strong {
    font-size: 0.94rem;
  }
  #dashboard[data-gauge-size="small"] .dashboard-gauge-headline span {
    font-size: 0.7rem;
  }
  #dashboard[data-gauge-size="small"] .dashboard-gauge-value-number {
    font-size: 1.65rem;
  }
  #dashboard[data-gauge-size="small"] .dashboard-gauge-visual {
    min-height: 120px;
    height: 120px;
  }
  #dashboard[data-gauge-size="small"] .dashboard-gauge-card .gauge,
  #dashboard[data-gauge-size="small"] .dashboard-gauge-card svg.gauge {
    max-width: 190px;
    max-height: 125px;
  }
  #dashboard[data-gauge-size="large"] .dashboard-board-gauges {
    grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
    gap: 1.15rem;
    padding: 1.2rem;
  }
  #dashboard[data-gauge-size="large"] .dashboard-gauge-card {
    min-height: 350px;
    padding: 1.35rem;
  }
  #dashboard[data-gauge-size="large"] .dashboard-gauge-headline strong {
    font-size: 1.25rem;
  }
  #dashboard[data-gauge-size="large"] .dashboard-gauge-headline span {
    font-size: 0.9rem;
  }
  #dashboard[data-gauge-size="large"] .dashboard-gauge-value-number {
    font-size: clamp(2.35rem, 4vw, 3rem);
  }
  #dashboard[data-gauge-size="large"] .dashboard-gauge-value-unit {
    font-size: 1.05rem;
  }
  #dashboard[data-gauge-size="large"] .dashboard-gauge-visual {
    min-height: 210px;
    height: 210px;
  }
  #dashboard[data-gauge-size="large"] .dashboard-gauge-card .gauge,
  #dashboard[data-gauge-size="large"] .dashboard-gauge-card svg.gauge {
    max-width: 325px;
    max-height: 215px;
  }
  #dashboard[data-gauge-size="large"] .dashboard-gauge-scale {
    font-size: 0.92rem;
  }
  #dashboard[data-gauge-size="large"] .dashboard-gauge-scale small {
    font-size: 0.7rem;
  }
  .tab-section-card {
    padding: clamp(1rem, 2.5vw, 1.5rem);
    border-radius: 1.25rem;
    box-shadow: 0 18px 45px rgba(15, 23, 42, 0.07);
  }
  .tab-section-title h3 {
    font-size: 1.25rem;
  }
  .chart-scroll-shell,
  .chart-panel-surface,
  .event-detail-disclosure,
  #mapContainer .map-shell {
    border-radius: 1.1rem;
  }
  @media (max-width: 991.98px) {
    .internal-hero {
      grid-template-columns: 1fr;
      min-height: 0;
    }
    .internal-hero-status {
      width: min(100%, 340px);
    }
    .internal-tabs-bar {
      align-items: stretch;
      flex-wrap: wrap;
    }
    #internalTabs {
      flex: 1 1 100%;
      overflow-x: auto;
      flex-wrap: nowrap;
    }
    .dashboard-tab-tools {
      width: 100%;
      justify-content: flex-end;
    }
  }
  @media (max-width: 767.98px) {
    .mds-page-internal .main-container {
      width: calc(100% - 20px);
    }
    .internal-hero {
      gap: 0.55rem;
      padding: 1.05rem 1.15rem;
      border-radius: 1.2rem;
    }
    .mds-page-internal .internal-hero h1 {
      font-size: clamp(1.7rem, 8vw, 2.2rem);
      line-height: 1.04;
    }
    .internal-hero p {
      margin-top: 0.45rem;
      font-size: 0.92rem;
      line-height: 1.4;
    }
    .internal-hero-status {
      display: flex;
      width: auto;
    }
    .internal-hero-stat {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      min-width: 0;
      width: max-content;
      padding: 0.5rem 0.7rem;
    }
    .internal-hero-stat strong {
      font-size: 1.25rem;
    }
    .internal-hero-stat span {
      margin-top: 0;
      font-size: 0.68rem;
    }
    #internalTabs .nav-link {
      gap: 0.4rem;
      padding: 0.62rem 0.72rem;
      font-size: 0.86rem;
    }
    .internal-tab-shell > .tab-pane {
      padding: 0.85rem 0 0;
    }
    .dashboard-tab-tools {
      width: 100%;
      justify-content: flex-start;
      flex-wrap: wrap;
    }
    .dashboard-gauge-size-picker-label {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
    }
    .dashboard-tab-tools .form-switch {
      min-width: 0;
    }
    .dashboard-board-header {
      padding: 1rem;
    }
    .dashboard-board-gauges {
      grid-template-columns: 1fr;
      padding: 0.8rem;
    }
    #dashboard[data-gauge-size] .dashboard-board-gauges {
      grid-template-columns: 1fr;
    }
    #dashboard[data-gauge-size="large"] .dashboard-gauge-card {
      min-height: 330px;
      padding: 1.15rem;
    }
    #dashboard[data-gauge-size="large"] .dashboard-gauge-visual {
      min-height: 195px;
      height: 195px;
    }
    .dashboard-gauge-card {
      min-height: 260px;
    }
    .dashboard-board-toggle {
      width: 2.25rem;
      padding: 0.4rem;
    }
    .dashboard-board-toggle span {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      white-space: nowrap;
      border: 0;
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
  const mdsCsrfToken = <?php echo json_encode(mds_get_csrf_token(), JSON_UNESCAPED_SLASHES); ?>;
  window.preferredChartWindowDays = <?php echo (int)$preferredChartWindowDays; ?>;
  window.eventTimelineWindowHours = <?php echo (int)$eventTimelineWindowHours; ?>;
  window.mdsI18n = <?php echo json_encode(array(
    'onlyThis' => mds_t('js.only_this'),
    'noEvents' => mds_t('js.no_events'),
    'hoursPerDay' => mds_t('js.hours_per_day'),
    'noSelectedEvents' => mds_t('js.no_selected_events'),
    'onlineSuffix' => mds_t('js.online_suffix'),
    'standbySuffix' => mds_t('js.standby_suffix'),
    'openEnded' => mds_t('js.open_ended'),
    'onlineHours' => mds_t('js.online_hours'),
    'standbyHours' => mds_t('js.standby_hours'),
    'windowHours' => mds_t('js.window_hours'),
    'sensorOrderSaved' => mds_t('js.sensor_order_saved'),
    'sensorOrderFailed' => mds_t('js.sensor_order_failed'),
  ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  setInterval(function() {
    // run every 30 seconds
    updateGauges();
  }, DashboardUpdateInterval);
</script>

  <div class="main-container">
  <div class="internal-hero">
    <div class="internal-hero-copy">
      <span class="internal-hero-eyebrow"><?php echo htmlspecialchars(mds_t('internal.dashboard'), ENT_QUOTES, 'UTF-8'); ?></span>
      <h1><?php echo htmlspecialchars(mds_t('internal.welcome'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($currentUser->getFirstName(), ENT_QUOTES, 'UTF-8'); ?>
      <?php
      if (configuration::$demoMode) {
        echo htmlspecialchars("  (" . mds_t('internal.demo_mode') . ")", ENT_QUOTES, 'UTF-8');
      }
      ?></h1>
      <p><?php echo htmlspecialchars(mds_t('internal.hero_text'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <div class="internal-hero-status" aria-label="<?php echo htmlspecialchars(mds_t('internal.dashboard'), ENT_QUOTES, 'UTF-8'); ?>">
      <div class="internal-hero-stat"><strong><?php echo count($boardObjsArray); ?></strong><span><?php echo htmlspecialchars(mds_t('common.devices'), ENT_QUOTES, 'UTF-8'); ?></span></div>
    </div>
  </div>
  <div class="container" style="padding: 0px">
    <div id="alert-container">
      <?php
        if($showInstallAlert) {
          echo "<div class='alert mds-alert alert-dismissible show' id='deployment-notice' role='alert'><div><strong>" . htmlspecialchars(mds_t('internal.deployment_notice'), ENT_QUOTES, 'UTF-8') . "</strong><p>" . htmlspecialchars(mds_t('internal.remove_install'), ENT_QUOTES, 'UTF-8') . "</p></div><button type='button' class='btn-close' id='deployment-notice-dismiss' aria-label='" . htmlspecialchars(mds_t('common.close'), ENT_QUOTES, 'UTF-8') . "'></button></div>";
        }

        if (!$hasBoards) {
          echo "<div class='mds-alert' role='alert'><div><strong>" . htmlspecialchars(mds_t('internal.no_board_title'), ENT_QUOTES, 'UTF-8') . "</strong><p>" . htmlspecialchars(mds_t('internal.no_board_text'), ENT_QUOTES, 'UTF-8') . "</p></div></div>";
        }
        ?>
    </div>

    <?php if ($showInstallAlert) { ?>
    <script>
    (function () {
      const notice = document.getElementById('deployment-notice');
      const dismissButton = document.getElementById('deployment-notice-dismiss');
      if (!notice || !dismissButton) {
        return;
      }

      const storageKey = 'mds.deploymentNoticeDismissed';
      try {
        if (window.sessionStorage.getItem(storageKey) === '1') {
          notice.remove();
          return;
        }
      } catch (error) {
        // Closing remains available when browser storage is disabled.
      }

      dismissButton.addEventListener('click', function () {
        try {
          window.sessionStorage.setItem(storageKey, '1');
        } catch (error) {
          // The visual dismissal must not depend on browser storage.
        }
        notice.remove();
      });
    }());
    </script>
    <?php } ?>

    <!-- Nav tabs and dashboard-only controls -->
    <div class="internal-tabs-bar">
    <ul class="nav nav-tabs" id="internalTabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#dashboard" role="tab"><i class="bi bi-grid-1x2-fill" aria-hidden="true"></i><?php echo htmlspecialchars(mds_t('internal.dashboard'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#charts" role="tab"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i><?php echo htmlspecialchars(mds_t('internal.charts'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#boards" role="tab"><i class="bi bi-cpu-fill" aria-hidden="true"></i><?php echo htmlspecialchars(mds_t('common.boards'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="hrefmap" data-bs-toggle="tab" href="#mapContainer" role="tab"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i><?php echo htmlspecialchars(mds_t('internal.map'), ENT_QUOTES, 'UTF-8'); ?></a>
      </li>
      <?php
        if(myFunctions::isUserAdmin((int)$currentUser->getId()) ) {
      ?>
        <li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#debug' role='tab'><i class='bi bi-terminal-fill' aria-hidden='true'></i><?php echo htmlspecialchars(mds_t('internal.debug'), ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php
        }
        ?>
    </ul>
    <div class="dashboard-tab-tools" id="dashboard-tab-tools">
      <button type="button" class="dashboard-layout-button" id="click_lockUnlock" data-bs-toggle="collapse" data-bs-target=".multi-collapse" aria-expanded="false">
        <i class="bi bi-lock-fill" aria-hidden="true"></i><span><?php echo htmlspecialchars(mds_current_language() === 'de' ? 'Layout' : 'Layout', ENT_QUOTES, 'UTF-8'); ?></span>
      </button>
      <div class="dashboard-gauge-size-picker" id="dashboard-gauge-size-picker" role="group" aria-label="<?php echo htmlspecialchars(mds_t('internal.gauge_size'), ENT_QUOTES, 'UTF-8'); ?>">
        <span class="dashboard-gauge-size-picker-label"><?php echo htmlspecialchars(mds_t('internal.gauge_size'), ENT_QUOTES, 'UTF-8'); ?></span>
        <button type="button" class="dashboard-gauge-size-button" data-gauge-size="small" aria-pressed="false" title="<?php echo htmlspecialchars(mds_t('internal.gauge_size_small'), ENT_QUOTES, 'UTF-8'); ?>">S</button>
        <button type="button" class="dashboard-gauge-size-button" data-gauge-size="medium" aria-pressed="true" title="<?php echo htmlspecialchars(mds_t('internal.gauge_size_medium'), ENT_QUOTES, 'UTF-8'); ?>">M</button>
        <button type="button" class="dashboard-gauge-size-button" data-gauge-size="large" aria-pressed="false" title="<?php echo htmlspecialchars(mds_t('internal.gauge_size_large'), ENT_QUOTES, 'UTF-8'); ?>">L</button>
      </div>
      <div class="form-check form-switch m-0">
        <input class="form-check-input" type="checkbox" id="dashboard-online-only-toggle" <?php if ((int)$dashboardOnlineOnlyDefault === 1) { echo 'checked'; } ?>>
        <label class="form-check-label" for="dashboard-online-only-toggle"><?php echo htmlspecialchars(mds_t('internal.only_online_devices'), ENT_QUOTES, 'UTF-8'); ?></label>
      </div>
    </div>
    </div>

    <div class="tab-content internal-tab-shell">

      <!-- Show dashboard -->
      <div class="container tab-pane fade show active position-relative" id="dashboard" data-gauge-size="medium">
        <div class="dashboard-shell">
          <div class="page-content page-container" id="page-content" style="--bs-gutter-x: 0rem; "></div>
          <div class="container" style="--bs-gutter-x: 0; padding-right: 0px; padding-left: 0px;">
            <?php
            foreach($boardObjsArray as $singleRowmyboard) {
              if($singleRowmyboard->isOnDashboard() == 1) {
                $deviceOnline = checkDeviceIsOnline($singleRowmyboard->getId());
                $mySensors2 = myFunctions::getAllSensorsOfBoardWithDashboardWithTypeName($singleRowmyboard->getId());
                if (is_array($mySensors2)) {
                  $mySensors2 = array_values(array_filter($mySensors2, function ($sensorRow) use ($currentUser) {
                    return myFunctions::canUserAccessSensor((int)$currentUser->getId(), (int)($sensorRow['id'] ?? 0));
                  }));
                }
                $sensorMaxAgeMinutes = max(1, (int)$singleRowmyboard->getOfflineDataTimer());
                $sensorActivitySummary = myFunctions::getSensorActivitySummary(
                  array_column($mySensors2 ?: array(), 'id'),
                  $sensorMaxAgeMinutes
                );
                $boardEventStatus = $eventStatusByBoard[$singleRowmyboard->getId()] ?? null;
                $boardGaugeCount = 0;
                ?>
                  <section class="dashboard-board-card <?php if(!$deviceOnline) { echo 'is-offline'; } ?> <?php if ((int)$dashboardOnlineOnlyDefault === 1 && !$deviceOnline) { echo 'dashboard-board-hidden'; } ?>" data-dashboard-board-id="<?php echo $singleRowmyboard->getId(); ?>" data-dashboard-online="<?php echo $deviceOnline ? '1' : '0'; ?>">
                    <div class="dashboard-board-header">
                      <div class="dashboard-board-title">
                        <h3><?php echo htmlspecialchars($singleRowmyboard->getName(), ENT_QUOTES, 'UTF-8'); ?></h3>
                        <div class="dashboard-board-summary">
                          <span><i class="bi bi-cloud-arrow-down" aria-hidden="true"></i><?php echo htmlspecialchars(mds_t('internal.firmware_version', array($singleRowmyboard->getFirmwareVersion() ?: '-')), ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                      </div>
                      <div class="dashboard-board-badges">
                        <span class="badge <?php echo $deviceOnline ? 'bg-success' : 'bg-danger'; ?>"><?php echo htmlspecialchars($deviceOnline ? mds_t('common.online') : mds_t('common.offline'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span
                          class="badge text-bg-light"
                          title="<?php echo htmlspecialchars(mds_t('internal.sensor_activity_hint', array($sensorActivitySummary['configured'], $sensorActivitySummary['withData'], $sensorActivitySummary['current'])), ENT_QUOTES, 'UTF-8'); ?>"
                        ><?php echo htmlspecialchars(mds_t('internal.sensor_activity_badge', array($sensorActivitySummary['current'], $sensorActivitySummary['configured'])), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php if (is_array($boardEventStatus) && !empty($boardEventStatus['modeLabel'])) { ?>
                          <span class="badge <?php echo !empty($boardEventStatus['persistentOnline']) ? 'mds-persistent-online-badge' : 'mds-standby-badge'; ?>">
                            <?php echo htmlspecialchars($boardEventStatus['modeLabel'], ENT_QUOTES, 'UTF-8'); ?>
                          </span>
                        <?php } ?>
                        <button
                          type="button"
                          class="dashboard-board-toggle"
                          data-dashboard-gauge-toggle
                          data-board-id="<?php echo $singleRowmyboard->getId(); ?>"
                          data-show-label="<?php echo htmlspecialchars(mds_t('common.show_gauges'), ENT_QUOTES, 'UTF-8'); ?>"
                          data-hide-label="<?php echo htmlspecialchars(mds_t('common.hide_gauges'), ENT_QUOTES, 'UTF-8'); ?>"
                          aria-expanded="true"
                          aria-controls="gaugescontainer<?php echo $singleRowmyboard->getId(); ?>"
                          title="<?php echo htmlspecialchars(mds_t('common.hide_gauges'), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                          <i class="bi bi-chevron-up" aria-hidden="true"></i>
                          <span><?php echo htmlspecialchars(mds_t('common.hide_gauges'), ENT_QUOTES, 'UTF-8'); ?></span>
                        </button>
                      </div>
                    </div>
                    <div class="card-block dashboard-board-gauges" id="gaugescontainer<?php echo $singleRowmyboard->getId() ?>">
                <?php
                if ($mySensors2 == null) {
                  ?>
                    <div class='dashboard-empty-state'><?php echo htmlspecialchars(mds_t('internal.no_dashboard_sensors'), ENT_QUOTES, 'UTF-8'); ?></div>
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
                        $sensorReadingTimestamp = strtotime((string)($singleRowMySensorsLastTimeSeen['reading_time'] ?? ''));
                        $sensorDataCurrent = $sensorReadingTimestamp !== false && $sensorReadingTimestamp >= time() - ($sensorMaxAgeMinutes * 60);
                        $unitValue = html_entity_decode((string)($sensortype['siUnitVal' . $i] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if (($mySensors != null) && (is_array($SensorChannelConfigSingle)) && ($SensorChannelConfigSingle['onDashboard'] == 1) && ($numericCurrentChannelValue !== null)) {
                          $boardGaugeCount++;
                          ?>
                          <div
                            id='gauge<?php echo $singleRowMySensors['id'] . "." . $i; ?>'
                            data-id="<?php echo $SensorChannelConfigSingle['DashboardOrderNr']; ?>"
                            class='ui-state-default dashboard-gauge-card gauge-container <?php if(!$deviceOnline || !$sensorDataCurrent) { echo "disabled"; } ?>'
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
                                <span>
                                  <?php
                                    $gaugeGroupLabel = trim((string)$singleRowMySensors['name']);
                                    $gaugeTypeLabel = trim((string)$singleRowMySensors['typename']);
                                    echo htmlspecialchars(
                                      strcasecmp($gaugeGroupLabel, $gaugeTypeLabel) === 0
                                        ? $gaugeGroupLabel
                                        : $gaugeGroupLabel . ' · ' . $gaugeTypeLabel,
                                      ENT_QUOTES,
                                      'UTF-8'
                                    );
                                  ?>
                                </span>
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
                            <div class="dashboard-gauge-scale" aria-label="Gauge range">
                              <span><small><?php echo htmlspecialchars(mds_t('common.minimum'), ENT_QUOTES, 'UTF-8'); ?></small><?php echo htmlspecialchars(number_format((float)$SensorChannelConfigSingle['GaugeMinValue'], 1, '.', ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($unitValue, ENT_QUOTES, 'UTF-8'); ?></span>
                              <span><small><?php echo htmlspecialchars(mds_t('common.maximum'), ENT_QUOTES, 'UTF-8'); ?></small><?php echo htmlspecialchars(number_format((float)$SensorChannelConfigSingle['GaugeMaxValue'], 1, '.', ''), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($unitValue, ENT_QUOTES, 'UTF-8'); ?></span>
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
                    <div class='dashboard-empty-state'><?php echo htmlspecialchars(mds_t('internal.no_numeric_values'), ENT_QUOTES, 'UTF-8'); ?></div>
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
            window.eventTimelineLast24h = <?php echo json_encode($eventTimelineLast24h, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
          </script>
          <div id="chart-container">
            <div class="tab-section-card chart-panel">
              <div class="tab-section-title">
                <div>
                  <h3><?php echo htmlspecialchars(mds_t('internal.temperature_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p><?php echo htmlspecialchars(mds_t('internal.temperature_text'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="temperature"><?php echo htmlspecialchars(mds_t('common.show_all'), ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="temperature"><?php echo htmlspecialchars(mds_t('common.hide_all'), ENT_QUOTES, 'UTF-8'); ?></button>
              </div>
              </div>
              <div id="chart-device-filter-temperature" class="chart-device-filter d-flex flex-wrap gap-3 mb-2"></div>
              <div class="chart-scroll-shell" aria-label="<?php echo htmlspecialchars(mds_t('internal.temperature_title'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="chart-scroll-inner">
                  <canvas id="mycanvas"></canvas>
                </div>
              </div>
              <p class="chart-mobile-hint"><?php echo htmlspecialchars(mds_t('internal.chart_mobile_hint'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="tab-section-card chart-panel">
              <div class="tab-section-title">
                <div>
                  <h3><?php echo htmlspecialchars(mds_t('internal.adc_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p><?php echo htmlspecialchars(mds_t('internal.adc_text'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="adc"><?php echo htmlspecialchars(mds_t('common.show_all'), ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="adc"><?php echo htmlspecialchars(mds_t('common.hide_all'), ENT_QUOTES, 'UTF-8'); ?></button>
              </div>
              </div>
              <div id="chart-device-filter-adc" class="chart-device-filter d-flex flex-wrap gap-3 mb-2"></div>
              <div class="chart-scroll-shell" aria-label="<?php echo htmlspecialchars(mds_t('internal.adc_title'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="chart-scroll-inner">
                  <canvas id="mycanvas2"></canvas>
                </div>
              </div>
              <p class="chart-mobile-hint"><?php echo htmlspecialchars(mds_t('internal.chart_mobile_hint'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="tab-section-card chart-panel">
              <div class="tab-section-title">
                <div>
                  <h3><?php echo htmlspecialchars(mds_t('internal.other_sensors_title'), ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p><?php echo htmlspecialchars(mds_t('internal.other_sensors_text'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="other"><?php echo htmlspecialchars(mds_t('common.show_all'), ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="other"><?php echo htmlspecialchars(mds_t('common.hide_all'), ENT_QUOTES, 'UTF-8'); ?></button>
              </div>
              </div>
              <div id="chart-device-filter-other" class="chart-device-filter d-flex flex-wrap gap-3 mb-2"></div>
              <div class="chart-scroll-shell" aria-label="<?php echo htmlspecialchars(mds_t('internal.other_sensors_title'), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="chart-scroll-inner">
                  <canvas id="mycanvas3"></canvas>
                </div>
              </div>
              <p class="chart-mobile-hint"><?php echo htmlspecialchars(mds_t('internal.chart_mobile_hint'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="tab-section-card chart-panel">
              <div class="tab-section-title">
                  <div>
                    <?php $eventWindowTitle = (int)$preferredChartWindowDays === 1 ? mds_t('internal.last_24_hours') : mds_t('internal.last_days', array((int)$preferredChartWindowDays)); ?>
                  <h3><?php echo htmlspecialchars(mds_t('internal.esp_events'), ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p><?php echo htmlspecialchars(mds_t('internal.esp_events_text', array($eventWindowTitle)), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="events"><?php echo htmlspecialchars(mds_t('common.show_all'), ENT_QUOTES, 'UTF-8'); ?></button>
                  <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="events"><?php echo htmlspecialchars(mds_t('common.hide_all'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
              </div>
              <div id="chart-device-filter-events" class="chart-device-filter d-flex flex-wrap gap-3 mb-3"></div>
              <div class="chart-panel-surface mb-4">
                <div class="tab-section-title mb-3">
                  <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars(mds_t('internal.event_timeline_window_static'), ENT_QUOTES, 'UTF-8'); ?></h4>
                    <p><?php echo htmlspecialchars(mds_t('internal.event_timeline_window_static_text'), ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                  <div class="event-window-control">
                    <label for="eventTimelineWindowSelect"><?php echo htmlspecialchars(mds_t('internal.timeline_window_control'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <select id="eventTimelineWindowSelect" class="form-select form-select-sm">
                      <?php foreach (array(3, 6, 12, 24, 48, 72) as $timelineWindowOption) { ?>
                        <option value="<?php echo $timelineWindowOption; ?>" <?php if ((int)$eventTimelineWindowHours === $timelineWindowOption) { echo 'selected'; } ?>>
                          <?php echo htmlspecialchars(mds_t('settings.last_hours', array($timelineWindowOption)), ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
                <div id="eventTimelineWindowSummary" class="event-window-summary"></div>
                <div class="event-chart-legend">
                  <span class="event-chart-legend-item"><span class="event-chart-legend-dot is-online"></span><?php echo htmlspecialchars(mds_t('js.online_suffix'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <span class="event-chart-legend-item"><span class="event-chart-legend-dot is-standby"></span><?php echo htmlspecialchars(mds_t('js.standby_suffix'), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
                <div class="event-summary-chart-shell">
                  <div class="event-chart-scroll-inner">
                    <canvas id="eventTimeline24hCanvas"></canvas>
                  </div>
                </div>
              </div>
              <div class="chart-panel-surface mb-4">
                <div class="tab-section-title mb-3">
                  <div>
                    <h4 class="mb-1"><?php echo htmlspecialchars(mds_t('internal.event_history', array($eventWindowTitle)), ENT_QUOTES, 'UTF-8'); ?></h4>
                    <?php $windowLabel = $eventWindowTitle; ?>
                    <p><?php echo htmlspecialchars(mds_t('internal.event_history_text', array($windowLabel)), ENT_QUOTES, 'UTF-8'); ?></p>
                  </div>
                </div>
                <div class="event-summary-chart-shell">
                  <div class="event-chart-scroll-inner">
                    <canvas id="eventSummaryCanvas"></canvas>
                  </div>
                </div>
              </div>
              <details class="event-detail-disclosure mt-4">
                <summary><?php echo htmlspecialchars(mds_t('internal.detail_history'), ENT_QUOTES, 'UTF-8'); ?></summary>
                <div class="event-detail-body">
                  <p class="text-muted mb-3"><?php echo htmlspecialchars(mds_t('internal.detail_history_text'), ENT_QUOTES, 'UTF-8'); ?></p>
                  <div id="event-timeline-container" class="d-flex flex-column gap-3" data-server-rendered="1">
                    <?php if (empty($eventTimelineBoards)) { ?>
                      <div class="event-timeline-empty"><?php echo htmlspecialchars(mds_t('internal.no_events'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php } else { ?>
                      <?php foreach ($eventTimelineBoards as $eventTimelineBoard) { ?>
                        <?php $boardStatus = $eventStatusByBoard[(int)$eventTimelineBoard['boardId']] ?? null; ?>
                        <section class="event-timeline-board" data-event-board-id="<?php echo (int)$eventTimelineBoard['boardId']; ?>">
                          <div class="event-timeline-head">
                            <div class="event-timeline-head-main">
                              <strong><?php echo htmlspecialchars($eventTimelineBoard['boardName'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                              <span><?php echo htmlspecialchars(mds_t('internal.events_in_history', array(count($eventTimelineBoard['events']))), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <?php if (is_array($boardStatus)) { ?>
                              <div class="event-timeline-status">
                                <span class="event-timeline-status-pill <?php echo htmlspecialchars($boardStatus['currentClass'], ENT_QUOTES, 'UTF-8'); ?>">
                                  <?php echo htmlspecialchars(mds_t('internal.event_current_state', array($boardStatus['currentLabel'])), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <?php if (!empty($boardStatus['timestamp'])) { ?>
                                  <span class="event-timeline-status-pill">
                                    <?php echo htmlspecialchars(mds_t('internal.event_since', array($boardStatus['timestamp'])), ENT_QUOTES, 'UTF-8'); ?>
                                  </span>
                                <?php } ?>
                              </div>
                            <?php } ?>
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
                                      if (isset($eventTimelineEntry['durationSeconds']) && $eventTimelineEntry['durationSeconds'] !== null) {
                                        $detailParts[] = mds_t('internal.event_duration', array(mds_format_internal_duration($eventTimelineEntry['durationSeconds'])));
                                      } elseif (!empty($eventTimelineEntry['durationOpen'])) {
                                        $detailParts[] = mds_t('internal.event_duration_open');
                                      }
                                      if (!empty($eventTimelineEntry['rawLabel']) && $eventTimelineEntry['rawLabel'] !== $eventTimelineEntry['label']) {
                                        $detailParts[] = (mds_current_language() === 'de' ? 'Rohwert: ' : 'Raw value: ') . $eventTimelineEntry['rawLabel'];
                                      }
                                      if (!empty($eventTimelineEntry['fallbackTimestamp']) && $eventTimelineEntry['fallbackTimestamp'] !== $eventTimelineEntry['timestamp']) {
                                        $detailParts[] = (mds_current_language() === 'de' ? 'Datensatz: ' : 'Record: ') . $eventTimelineEntry['fallbackTimestamp'];
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
                  <h3><?php echo htmlspecialchars(mds_t('common.boards'), ENT_QUOTES, 'UTF-8'); ?></h3>
                  <p><?php echo htmlspecialchars(mds_t('internal.boards_text'), ENT_QUOTES, 'UTF-8'); ?></p>
                </div>
              </div>
              <div class="board-overview-list">
            <?php
            foreach($boardObjsArray as $singleBoardObj) {
              $transmissionPath = 0;
              $mySensors2 = myFunctions::getAllSensorsOfBoard($singleBoardObj->getId());
              if (is_array($mySensors2)) {
                $mySensors2 = array_values(array_filter($mySensors2, function ($sensorRow) use ($currentUser) {
                  return myFunctions::canUserAccessSensor((int)$currentUser->getId(), (int)($sensorRow['id'] ?? 0));
                }));
              }
              $boardOnlineStatus = false;
              $mySensorIdList = null;
              if ($mySensors2 == null) {
                ?>
                  <div class='container mt-2'>
                    <span class='badge bg-danger mr-2' style='width: 55px;'><?php echo htmlspecialchars(mds_t('common.offline'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                  <span class='badge bg-success mr-2' style='width: 55px;'><?php echo htmlspecialchars(mds_t('common.online'), ENT_QUOTES, 'UTF-8'); ?></span>
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
                  <span class='badge bg-danger mr-2' style='width: 55px;'><?php echo htmlspecialchars(mds_t('common.offline'), ENT_QUOTES, 'UTF-8'); ?></span>
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
              <h3><?php echo htmlspecialchars(mds_t('internal.debug'), ENT_QUOTES, 'UTF-8'); ?></h3>
              <p><?php echo htmlspecialchars(mds_t('internal.debug_text'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
          </div>
          <div class="p-2 d-flex flex-column gap-2" id="chart-container-debug">
            <p class="mb-0 text-muted"><?php echo htmlspecialchars(mds_t('internal.debug_table_text'), ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        <?php
            include_once dirname(__DIR__) . "/app/Http/Webhooks/TTN/index.php"; // NOSONAR - debug bootstrap include
          ?>
        </div>
      </div>

      <!-- Show map -->
      <div class="container tab-pane fade pl-0" id="mapContainer">
        <div class="row mt-2">
          <div class="container map-shell">
            <?php include_once __DIR__ . "/openstreetmaps.php"; // NOSONAR - shared map include ?>
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
        const layoutIcon = $("i", this);
        layoutIcon.toggleClass("bi-lock-fill bi-unlock-fill");
        if (layoutIcon.hasClass("bi-unlock-fill")) {
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
                    csrf_token: mdsCsrfToken,
                    channel: SensorIdChannel[1],
                    orderNumber: $( this ).attr("data-id"),
                    id: SensorIdChannel[0] }
              })
                .done(function( response ) {
                  if (!onceSensorOrderDone) {
	                    g = document.createElement('div');
	                    g.setAttribute("class", "alert alert-success alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
	                    g.setAttribute("role", "alert");
	                    g.textContent = window.mdsI18n?.sensorOrderSaved || "Sensor order saved.";
	                    const closeButton = document.createElement('button');
	                    closeButton.type = 'button';
	                    closeButton.className = 'btn-close';
	                    closeButton.setAttribute('data-bs-dismiss', 'alert');
	                    closeButton.setAttribute('aria-label', 'Close');
	                    g.appendChild(closeButton);
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
	                    g.textContent = window.mdsI18n?.sensorOrderFailed || "Sensor order not saved.";
	                    const closeButton = document.createElement('button');
	                    closeButton.type = 'button';
	                    closeButton.className = 'btn-close';
	                    closeButton.setAttribute('data-bs-dismiss', 'alert');
	                    closeButton.setAttribute('aria-label', 'Close');
	                    g.appendChild(closeButton);
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
          $('#dashboard-tab-tools').prop('hidden', targetSelector !== '#dashboard');

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
          const modalContent = document.querySelector('.modal-content');
          if (!modalContent) {
            return;
          }
          const modalDocument = new DOMParser().parseFromString(response, 'text/html');
          modalContent.replaceChildren(...Array.from(modalDocument.body.childNodes));
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
    include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php"; // NOSONAR - shared template include
  ?>
