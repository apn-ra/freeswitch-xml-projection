# Chaos smoke test

The chaos smoke suite is an opt-in operator harness for challenging the local Docker FreeSWITCH `mod_xml_curl` directory `sip_auth` path under controlled failure modes. It is not normal CI, not a production resilience framework, and not FreeSWITCH management code.

Run it only on a workstation or lab host that has the local FreeSWITCH Docker lab available.

```bash
FREESWITCH_XML_PROJECTION_CHAOS_SMOKE=1 composer chaos:smoke
```

The PHPUnit wrapper is also opt-in:

```bash
composer test:chaos
```

Without `FREESWITCH_XML_PROJECTION_CHAOS_SMOKE=1`, the runner exits with a skip message and the PHPUnit test skips before Docker or FreeSWITCH calls are made.

## Difference From Live Smoke

The live smoke suite proves the happy path can serve a real Docker FreeSWITCH `directory` / `sip_auth` request end to end.

The chaos smoke suite first proves that same baseline, then intentionally breaks the XML curl path in bounded ways to confirm the package boundary and temporary lab wiring fail safely and clean up after themselves.

## What It Proves

- A baseline real `directory` / `sip_auth` request can still be parsed and answered with package-rendered directory XML.
- Unknown users can receive package-rendered not-found XML.
- XML curl server unavailability is contained to the lab integration path.
- Slow responses, malformed XML, HTTP 500, and oversized XML complete within bounded time and do not leave committed artifacts.
- A small REGISTER burst does not corrupt redacted event capture.
- Sensitive request fields are redacted in `/tmp/freeswitch-xml-projection-chaos-smoke/events.ndjson`.
- Temporary `xml_curl.conf.xml` changes are restored.

## What It Does Not Prove

- APNTalk tenant resolution.
- Provider binding lookup.
- Credential storage, decryption, or rotation.
- FreeSWITCH gateway authorization.
- HTTP edge controls such as Basic auth, mTLS, IP allowlists, rate limits, or audit logging.
- Production SIP registration success.
- High-volume load behavior.

Those responsibilities remain outside this package.

## Environment

Required gate:

```text
FREESWITCH_XML_PROJECTION_CHAOS_SMOKE=1
```

Common overrides:

```text
FREESWITCH_XML_PROJECTION_DOCKER_COMPOSE=docker/docker-compose.yml
FREESWITCH_XML_PROJECTION_DOCKER_SERVICE=lab01
FREESWITCH_XML_PROJECTION_CONTAINER=freeswitch
FREESWITCH_XML_PROJECTION_CAPTURE_HOST=127.0.0.1
FREESWITCH_XML_PROJECTION_CHAOS_PORT=18081
FREESWITCH_XML_PROJECTION_SIP_HOST=127.0.0.1
FREESWITCH_XML_PROJECTION_SIP_PORT=5060
FREESWITCH_XML_PROJECTION_TEST_USER=1001
FREESWITCH_XML_PROJECTION_TEST_DOMAIN=127.0.0.1
FREESWITCH_XML_PROJECTION_TEST_PASSWORD=capture-password
FREESWITCH_XML_PROJECTION_FS_CLI_PASSWORD=
```

`FREESWITCH_XML_PROJECTION_FS_CLI_PASSWORD` is optional. Set it only in the shell when the local lab requires an `fs_cli` password. Do not commit it.

## Scenarios

- `baseline_success`: package-rendered directory XML for the configured fake user.
- `unknown_user_not_found`: package-rendered not-found XML.
- `server_unavailable`: FreeSWITCH points to an unused local XML curl port.
- `slow_response`: the XML curl endpoint sleeps longer than the configured timeout.
- `malformed_xml`: the XML curl endpoint returns malformed XML.
- `http_500`: the XML curl endpoint returns HTTP 500.
- `oversized`: the XML curl endpoint returns an oversized non-secret XML document.
- `concurrent_burst`: a small burst of fake SIP REGISTER attempts.
- `secret_leakage_check`: tracked-file scan for live `fs_cli` password and non-default test password values, plus raw capture JSON paths.
- `cleanup_resilience`: restore XML curl config, turn debug off, stop the PHP server, and remove temporary files.

## Files

- `tools/chaos-smoke/chaos-xml-curl-server.php` is the scenario-aware XML curl endpoint.
- `tools/chaos-smoke/chaos-scenarios.php` lists the bounded chaos scenarios.
- `tools/chaos-smoke/run-chaos-smoke.php` orchestrates Docker, temporary config, scenarios, assertions, and cleanup.
- `tests/Live/FreeSwitchXmlCurlChaosSmokeTest.php` is a skipped-by-default PHPUnit wrapper.

## Cleanup

The runner backs up:

```text
docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml
```

to:

```text
/tmp/freeswitch-xml-projection-chaos-smoke/xml_curl.conf.xml.before
```

It then writes a temporary directory binding to the chaos PHP endpoint or to an unused local port for the unavailable-server scenario. On shutdown it attempts to:

- run `xml_curl debug_off`
- restore the original `xml_curl.conf.xml`
- stop the PHP built-in server
- remove temporary chaos files under `/tmp/freeswitch-xml-projection-chaos-smoke`
- verify there is no git diff for the temporary Docker config

## Secret Safety

The chaos harness uses fake defaults. If you override them, use lab-only values.

Do not commit:

- live SIP passwords
- raw capture JSON
- authorization headers
- digest nonce, cnonce, URI, or response values
- gateway credentials
- FreeSWITCH logs
- recordings
- generated `freeswitch.xml.fsxml`
- local runtime dumps

The chaos event log redacts exact sensitive keys including `password`, `vm-password`, `reverse-auth-pass`, `sip_auth_response`, `sip_auth_nonce`, `sip_auth_cnonce`, `sip_auth_uri`, `Authorization`, `authorization`, `gateway-credentials`, and `gateway_credentials`.

It also redacts keys containing `pass`, `password`, `secret`, `token`, `authorization`, `nonce`, `cnonce`, `response`, or `credentials`.

## Troubleshooting

If the PHP server is unreachable, check whether `FREESWITCH_XML_PROJECTION_CHAOS_PORT` is already in use and whether host networking can reach `127.0.0.1`.

If `mod_xml_curl` is not loaded, inspect the local FreeSWITCH `modules.conf.xml` and rebuild or restart the Docker service.

If the SIP port is different, set `FREESWITCH_XML_PROJECTION_SIP_PORT` to the internal Sofia profile port.

If no `sip_auth` request is observed in the baseline scenario, run the live smoke first and confirm the Docker lab still works.

Use `xml_curl debug_on` only in a local lab. It can expose request and response data in FreeSWITCH logs. The runner attempts to turn it off during cleanup.
