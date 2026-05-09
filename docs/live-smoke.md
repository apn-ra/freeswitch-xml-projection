# Live smoke test

The live smoke suite is an opt-in operator harness for validating this package against a real local Docker FreeSWITCH `mod_xml_curl` directory `sip_auth` flow. It is not part of normal CI and is not run by `composer check`.

Run it only on a workstation or lab host that has the local FreeSWITCH Docker lab available.

```bash
FREESWITCH_XML_PROJECTION_LIVE_SMOKE=1 composer live:smoke
```

The PHPUnit wrapper is also opt-in:

```bash
composer test:live
```

## What It Proves

- A thin PHP web app can receive a real FreeSWITCH `mod_xml_curl` directory `sip_auth` request.
- `XmlCurlRequestParser` can parse the received request.
- `DirectoryXmlRenderer` can render a valid APNTalk-style directory response.
- FreeSWITCH receives XML rendered by this package, not a hand-written XML string.
- Unknown users receive first-class FreeSWITCH not-found XML.
- Captured request fields are written only to `/tmp/freeswitch-xml-projection-live-smoke/latest.json` with sensitive values redacted.
- The temporary local `xml_curl.conf.xml` binding is restored after the run.

## What It Does Not Prove

- APNTalk tenant resolution.
- Provider binding lookup.
- Credential storage, decryption, or rotation.
- FreeSWITCH gateway authorization.
- HTTP edge controls such as Basic auth, mTLS, IP allowlists, rate limits, or audit logging.
- Production SIP registration success.

Those responsibilities remain outside this package.

## Environment

Required gate:

```text
FREESWITCH_XML_PROJECTION_LIVE_SMOKE=1
```

Common overrides:

```text
FREESWITCH_XML_PROJECTION_DOCKER_COMPOSE=docker/docker-compose.yml
FREESWITCH_XML_PROJECTION_DOCKER_SERVICE=lab01
FREESWITCH_XML_PROJECTION_CONTAINER=freeswitch
FREESWITCH_XML_PROJECTION_CAPTURE_HOST=127.0.0.1
FREESWITCH_XML_PROJECTION_CAPTURE_PORT=18080
FREESWITCH_XML_PROJECTION_SIP_HOST=127.0.0.1
FREESWITCH_XML_PROJECTION_SIP_PORT=5060
FREESWITCH_XML_PROJECTION_TEST_USER=1001
FREESWITCH_XML_PROJECTION_TEST_DOMAIN=127.0.0.1
FREESWITCH_XML_PROJECTION_TEST_PASSWORD=capture-password
FREESWITCH_XML_PROJECTION_FS_CLI_PASSWORD=
```

`FREESWITCH_XML_PROJECTION_FS_CLI_PASSWORD` is optional. Set it only in the shell when the local lab requires an `fs_cli` password. Do not commit it.

The live server accepts the observed SIP auth realm for the fake default user because FreeSWITCH may challenge with the Sofia profile realm rather than `127.0.0.1`. Set `FREESWITCH_XML_PROJECTION_TEST_DOMAIN` to a specific realm to make the run stricter.

## Files

- `tools/live-smoke/xml-curl-server.php` is the PHP XML curl endpoint used by the lab.
- `tools/live-smoke/send-register.php` sends a temporary UDP SIP `REGISTER` probe with fake credentials.
- `tools/live-smoke/run-live-smoke.php` orchestrates Docker, temporary config, the PHP endpoint, the SIP probe, assertions, and cleanup.
- `tests/Live/FreeSwitchXmlCurlLiveSmokeTest.php` is a skipped-by-default PHPUnit wrapper.

## Cleanup

The runner backs up:

```text
docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml
```

to:

```text
/tmp/freeswitch-xml-projection-live-smoke/xml_curl.conf.xml.before
```

It then writes a temporary directory binding to the local PHP endpoint. On shutdown it attempts to:

- run `xml_curl debug_off`
- restore the original `xml_curl.conf.xml`
- stop the PHP built-in server
- verify there is no git diff for the temporary Docker config

## Secret Safety

The live harness uses fake defaults. If you override them, use lab-only values.

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

The capture JSON redacts exact sensitive keys including `password`, `vm-password`, `reverse-auth-pass`, `sip_auth_response`, `sip_auth_nonce`, `sip_auth_cnonce`, `sip_auth_uri`, `Authorization`, `authorization`, `gateway-credentials`, and `gateway_credentials`.

It also redacts keys containing `pass`, `password`, `secret`, `token`, `authorization`, `nonce`, `cnonce`, `response`, or `credentials`.

## Troubleshooting

If the PHP server is unreachable, check whether `FREESWITCH_XML_PROJECTION_CAPTURE_PORT` is already in use and whether host networking can reach `127.0.0.1`.

If `mod_xml_curl` is not loaded, inspect the local FreeSWITCH `modules.conf.xml` and rebuild or restart the Docker service.

If the SIP port is different, set `FREESWITCH_XML_PROJECTION_SIP_PORT` to the internal Sofia profile port.

If no `sip_auth` request is observed, confirm the temporary XML curl binding was written, FreeSWITCH reloaded or restarted, and the SIP probe reached the expected profile.

Use `xml_curl debug_on` only in a local lab. It can expose request and response data in FreeSWITCH logs. The runner attempts to turn it off during cleanup.
