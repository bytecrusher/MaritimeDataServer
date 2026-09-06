const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const events = {};
const chart = () => ({ data: { datasets: [] }, update() {} });
const context = {
  console: { error() {} },
  document: { hidden: false, addEventListener(name, callback) { events[name] = callback; },
    getElementById() { return { classList: { contains() { return true; } } }; } },
  window: { addEventListener() {}, myChart: chart(), myChart2: chart(), myChart3: chart() },
  gaugesArrayHelperBig: [
    { sensorId: 59, channelNr: 1, typename: 'DS18B20', BoardId: 10, BoardName: 'Boat', NameOfSensors: 'Battery temperature' },
    { sensorId: 57, channelNr: 1, typename: 'ADC', BoardId: 10, BoardName: 'Boat', NameOfSensors: 'Voltage' },
    { sensorId: 57, channelNr: 2, typename: 'ADC', BoardId: 10, BoardName: 'Boat', NameOfSensors: 'Capacity' },
  ],
};
let ready;
context.$ = () => ({ ready(callback) { ready = callback; } });
vm.createContext(context);
vm.runInContext(fs.readFileSync(path.join(__dirname, '../public/assets/js/app.js'), 'utf8'), context);

const row = (time, value1, value2 = '100') => ({ timestamp: Date.parse('2026-09-02T' + time + '+02:00'), value1, value2 });

async function run() {
  const points = context.sensorChartPoints([
    row('12:03:00', '13'), row('12:01:00', '12'), row('12:02:00', ''),
    row('12:04:00', 'Sleep'), row('12:05:00', '0'), row('12:03:00', '14'),
  ], 1);
  assert.equal(points.length, 5);
  assert.deepEqual(Array.from(points, point => point.y), [12, null, 14, null, 0]);
  assert.equal(points[2].x - points[0].x, 120000);
  assert.equal(context.sensorChartTimestamp(row('12:00:00', 1)), Date.parse('2026-09-02T10:00:00Z'));
  assert.ok(Number.isNaN(context.sensorChartTimestamp({ val_date: '02.09.2026', val_time: '12:00:00' })), 'Do not guess a browser timezone for legacy wall-clock strings.');
  assert.equal(context.getSensorChartWindowDays(), 7);
  context.window.preferredChartWindowDays = 30;
  assert.equal(context.getSensorChartWindowDays(), 30);
  assert.ok(Number.isNaN(context.sensorChartTimestamp({})));
  assert.equal(context.getChartKeyForDataset({ typename: 'BME280', channelNr: 1, NameOfSensors: 'Renamed' }), 'temperature');
  assert.equal(context.getChartKeyForDataset({ typename: 'BME280', channelNr: 2 }), 'other');
  assert.equal(context.mdsLineChartOptions().scales.x.type, 'linear');

  let calls = [];
  let replies = [];
  context.$.ajax = options => {
    calls.push(options);
    return new Promise(resolve => replies.push(resolve));
  };
  const first = context.refreshSensorCharts();
  assert.equal(context.refreshSensorCharts(), first, 'Overlapping refreshes must share one request batch.');
  assert.equal(calls.length, 2, 'Each sensor group must be fetched only once for all its channels.');
  assert.ok(calls.every(call => call.cache === false && call.timeout === 20000));
  assert.ok(calls.every(call => call.data.days === 30 && call.data.maxValues === 1000));
  replies[0]([row('12:01:00', '21')]);
  replies[1]([row('12:02:00', '12.5')]);
  await first;
  assert.equal(context.window.myChart.data.datasets.length, 1);
  assert.equal(context.window.myChart2.data.datasets.length, 2);
  assert.notEqual(context.window.myChart.data.datasets[0].data[0].x, context.window.myChart2.data.datasets[0].data[0].x,
    'Different sensors must retain their own timestamps.');
  context.window.myChart2.data.datasets[0].hidden = true;
  context.$.ajax = async () => [row('12:06:00', '15')];
  await context.refreshSensorCharts();
  assert.equal(context.window.myChart2.data.datasets.length, 2, 'Refresh replaces data rather than appending duplicate curves.');
  assert.equal(context.window.myChart2.data.datasets[0].hidden, true, 'Visibility survives refresh.');
  assert.equal(context.window.myChart2.data.datasets[0].data[0].y, 15);

  context.$.ajax = async () => { throw new Error('Network failure'); };
  await context.refreshSensorCharts();
  assert.equal(context.window.myChart2.data.datasets[0].data[0].y, 15, 'Transient failures must preserve existing data.');
  context.$.ajax = async () => [];
  await context.refreshSensorCharts();
  assert.equal(context.window.myChart2.data.datasets[0].data.length, 0, 'A successful empty response clears stale data.');

  context.gaugesArrayHelperBig = Array.from({ length: 6 }, (_, index) => ({
    sensorId: index + 100, channelNr: 1, typename: 'ADC', BoardId: 10,
  }));
  calls = [];
  replies = [];
  context.$.ajax = options => {
    calls.push(options);
    return new Promise(resolve => replies.push(resolve));
  };
  const bounded = context.refreshSensorCharts();
  assert.equal(calls.length, 4, 'No more than four sensor requests may start concurrently.');
  replies.splice(0).forEach(resolve => resolve([]));
  await new Promise(setImmediate);
  assert.equal(calls.length, 6);
  replies.splice(0).forEach(resolve => resolve([]));
  await bounded;

  // Test ready/visibility wiring independently of the event charts and DOM renderer.
  context.InitialSetupChart = () => {};
  context.initializeChartBoardFilters = () => {};
  context.initializeEventTimeline = () => {};
  let refreshes = 0;
  context.refreshSensorCharts = () => { refreshes++; };
  ready();
  events.visibilitychange();
  assert.equal(refreshes, 2);
  context.document.hidden = true;
  events.visibilitychange();
  assert.equal(refreshes, 2);
  console.log('Sensor chart tests passed.');
}
run().catch(error => { console.error(error); process.exitCode = 1; });
