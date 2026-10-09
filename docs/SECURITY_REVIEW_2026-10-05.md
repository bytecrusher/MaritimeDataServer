# Security finding reconciliation (local worktree)

Reviewed against development at e40f858 plus the uncommitted fixes, not against
the historical src/frontend and receiver paths. No production changes, pushes,
deployment, PR operations or Security Cloud status changes were performed for
this review. Existing untracked `... 2` files were not modified.

## Findings

Live Security Cloud reconciliation on 2026-10-09: the complete findings list
contained 36 findings for repository github-454326884 (MaritimeDataServer),
including 29 open (`new`) findings and 7 already marked `fixed`. All 29 open
finding IDs match the table below; no additional open finding was returned.
Repository-filtered API requests returned an empty list, so this inventory was
verified by filtering the complete, unpaginated 98-item response locally by
repository ID. No finding status was changed.

The existing local remediation was rechecked with `composer test`,
`node tests/CdnIntegrityTest.cjs`, `node tests/SensorChartsTest.cjs`,
`node tests/GaugeStatusTest.cjs`, and `git diff --check`; all passed on
2026-10-09. These checks do not establish production remediation. The local
branch still reports five commits behind its known tracking reference.

ID prefixes uniquely identify the supplied commit-scan findings. `Fixed` means
implemented locally, not deployed. Tests are SecurityRegressionTest,
TtnBoardIdentityTest, SensorChartHistoryTest and CdnIntegrityTest unless noted.

| Severity / ID | Finding | Current evidence and disposition |
|---|---|---|
| High 7ab0f71b | Compact MAC cross-tenant injection | Fixed in TtnBoardIdentity and TTN webhook: verified app/device tuple, reject conflicting MACs, no claiming empty bindings, recheck in ingest. Signed forwarding context prevents shared ingest keys forging TTN context. |
| High 7506ea47 | Unauthenticated TTN leaks API key | Fixed: webhook secret mandatory; configuration baseurl only from operator canonicalBaseUrl/MDS_BASE_URL; no Host-derived secret-bearing URL. Prepared debug inserts already present. |
| High 0ac4241a | Unauthenticated GPS locations | Session-derived user and parameterized getMyBoards already present. Added per-sensor access filtering for users sharing only a non-GPS sensor. |
| High 60996b4d | sensorId SQL injection / cross-board writes | INSERT already parameterized. Added sensorId format and board ownership check before sensor-specific mutation. Text-valued event channels retained. |
| High e8872081 | Unauthenticated TTN poisons sensors | Fixed by mandatory webhook secret and identity binding. A holder of the server webhook secret remains trusted. |
| High 4020ea8c | Public-identifier board claiming | Fixed in dbUpdateData::addNewBoardToUser: administrator approval now required; MAC/EUI alone never authorizes a normal user. Existing admin board ownership management remains. |
| High 631b0eca | Public debug stored XSS | Already guarded: app/Http/Webhooks/TTN/index.php requires admin session and uses debugTableCell/htmlspecialchars for cells. Additional log-script JSON escaping fixed in settings. |
| High d0a7a358 | Board-name unauthenticated SQL injection | Session identity and prepared queries already present in getBoardName/getMyBoards. Added GPS sensor visibility filtering. |
| High 034c26b4 | Legacy root config exposure | Fixed explicit config.json/.env deny rules at both root and public, in addition to existing private directory deny rules. Apache configuration still must honor .htaccess. |
| High 74d4001b | Non-admin API-key replacement | POST service already admin-only + page CSRF. Fixed secret rendering for non-admins, rejected short/empty saved keys; ingest now nonempty constant-time comparison. |
| High be7a453c | SQL injection sensor-board lookup | Already fixed: getdata authenticates session/token + sensor access; getBoardBySensorId uses placeholder and integer cast. |
| High a8359106 | Sensor API SQL injection | Already fixed: normalized integer list and bound IN placeholders in getLatestSensorData; authenticated authorized endpoint. |
| High b298fb09 | Host-derived TTN SSRF/key leak | Fixed canonical outbound URL as above; request Host and forwarded proto no longer define configuration baseurl. |
| High 8bf524fe76548191b33105d377281e8e | Stored XSS via unvalidated channel chart color | Fixed remaining server-side validation and form sinks: SensorColor accepts only #RRGGBB before all three color-writing update methods; read helpers normalize invalid historical colors without modifying DB data; all four form color attributes are escaped. Dashboard already uses escaped data-chart-color read via dataset, not executable PHP-generated JS. formSensors POST already enforces session, CSRF and canUserEditSensor for the posted sensor before writes; GET service also enforces edit permission. SensorColorSecurityTest covers malicious quotes/script tags, control characters, invalid types, all color fields and source guard ordering. |
| Medium b7f2687114888191ad61bf9812b15ef0 | NUL timestamps crash history | Fixed: catch ValueError and use receipt timestamp; ingestion rejects malformed date/time strings. Regression includes embedded NUL. |
| Medium 227a86b4 | Shared-key metadata overwrite | Fixed: metadata requires per-board credential, derives board ID from hash map, checks MAC consistency; no global-key fallback or implicit metadata provisioning. Measurement ingest accepts the same board key; provisioned boards reject unsigned global-key requests. |
| Medium 654a25c8 | Unauthenticated TTN creates owned boards | Fixed by mandatory secret + TtnBoardIdentity creating only unowned records. Resource quotas for authenticated compromised TTN senders are not added. |
| Medium 8580596e | Public log directory | Modern logs already outside public under var/log; root denies var. Added root/public legacy logs path deny. Web-server and leftover deployment files require separate verification. |
| Medium de2d2599 | Non-admin global demo mode | POST already admin-gated. Removed server-settings markup for non-admins; configuration values now normalized to booleans. |
| Medium 6466c4d5 | Tokenless activation | Fixed AccountActivation: random 256-bit tokens stored hashed, 24h expiry, replacement on resend, atomic one-time consumption. Bare numeric IDs no longer activate. |
| Medium a916935c | Empty ingest key after install | Fixed nonempty constant-time ingest authentication; installer/save require minimum 16 characters. Template no longer suggests short sample key. |
| Medium 2aa1208f | String false enables demo mode | Fixed installer, runtime loading and settings save with FILTER_VALIDATE_BOOLEAN. Template boolean false. |
| Medium 61313e1b | Settings disclose logs to all users | Fixed normal and fallback page-data paths: only admins load logs; only admins render server/migration/log panels. JSON embedded in script hex-escapes HTML delimiters. |
| Medium 19478bbc | CDN resources without SRI | Fixed 15 exact versioned resources using SHA-384 of fetched bytes; crossorigin=anonymous. CdnIntegrityTest checks tracked PHP tags. A compromised origin at pinning time is outside SRI protection. |
| Low 413ca2fe | Temperature split drops alerts | TemperatureAlertTransfer atomically copies source metadata/thresholds/state/gauges and alert recipients with originals journaled. Idempotent; differing active destination alerts cause rollback and warning rather than overwrite. Legacy Status value3 remains supplied during transition. |
| Low 58985364 | Missing config bootstrap warning | Runtime already checks file existence. Added malformed JSON guard and missing installer config defaults; installer config path is config/config.json. Production display_errors must still be disabled. |
| Low 06cb36af | Host header installer redirect | Header already uses mds_route_path relative redirect. Outbound canonical base no longer derived from Host; mount-path routing retained. |
| Informational 963a31bd | Unauthenticated simulator SSRF | Already admin session + CSRF + exact configured URL allowlist + http(s). Added connect/total timeout. Explicit operator allowlists may intentionally contain private addresses. |
| Informational 2be57f74 | Config move breaks installer/TTN | Current installer/runtime already share config/config.json. Canonical forwarding now explicit and includes operator-provided installation subpath. |

## Deployment prerequisites (not performed)

1. Back up configuration/database. Review all diffs and update from the remote
   branch separately; this checkout started behind origin/development.
2. Set `canonicalBaseUrl` (e.g. `https://mds-git.derguntmar.de`) in config/config.json
   or `MDS_BASE_URL` in the PHP environment. An admin UI field and installer field
   are provided. Missing URL deliberately disables TTN forwarding and activation
   mail rather than trusting Host. Configure a nonempty `ttnWebhookSecret` and
   matching TTN webhook header.
3. Apply both 2026-10-05 SQL migrations. They are registered in the settings
   migration list and included in fresh installation schema. They only create
   token/journal tables. Temperature transfer runs on a subsequent valid TTN
   temperature ingestion after the reading was stored. Conflicts remain visible
   as warnings and retain legacy alarm delivery.
4. Issue a board key using `php tools/maintenance/issue_board_key.php BOARD_ID`.
   Run as an operator with appropriate config-file ownership; the file is saved
   with mode 0600. It must remain readable/writable by the PHP service account.
   Put the displayed secret in that device's existing `board.apiKey` field for
   measurements and metadata. Hashes only are stored in `boardApiKeyHashes`.
   Reissuing revokes the previous board key. No keys were issued during tests.
5. Existing shared keys continue to ingest measurements for not-yet-provisioned
   boards only; they never authorize the metadata endpoint. For provisioned boards,
   TTN uses signed forwarding, and direct devices must use their own key.
   This is an explicit staged transition, not complete isolation for unmigrated
   shared-key devices. Finish provisioning every board, then retire the legacy key
   from devices. New boards must be provisioned by a trusted operator.
6. Old tokenless activation links are invalid; resend registration email. The
   activation migration must exist before enabling registration after rollout.
7. Verify Apache rules, alternate server configurations, obsolete root files,
   CDN loads and real MySQL/TTN/device/mail behavior before production release.
   Do not ship untracked `... 2` copies of PHP/config files: old endpoints can
   otherwise bypass new guards. They were deliberately left untouched locally.

## Verification limits

Regression tests use SQLite fixtures and local PHP/Node. They cover cross-board
MAC/key/sensor failures, activation replay/expiry/resend, alert transfer and conflict
rollback, forwarding body tampering/expiry, and source guards. MySQL advisory locks,
real migrations, rendered pages and production HTTP/mail behavior are not proven
by these tests. Existing no-op administrative permission paths and deployment
assumptions are recorded above, not represented as live verification.

Local results: `composer test`, `node tests/CdnIntegrityTest.cjs`,
`node tests/SensorChartsTest.cjs`, `node tests/GaugeStatusTest.cjs` all passed.
SensorColorSecurityTest additionally verifies all 29 supplied finding IDs appear
exactly once in the reconciliation table. Authorization/HTML wiring checks are
source regressions, not a substitute for an authenticated browser test.
PHP syntax checks passed for 127 non-duplicate PHP files under app/public/tests/
tools/bootstrap. `git diff --check` passed. Index empty; HEAD remains e40f858.
