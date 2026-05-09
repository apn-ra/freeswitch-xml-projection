# Fixture provenance

- [tests/Fixture/Requests/directory-sip-auth-minimal.php](../tests/Fixture/Requests/directory-sip-auth-minimal.php) is synthetic and intentionally minimal.
- [tests/Fixture/Requests/directory-gateways.php](../tests/Fixture/Requests/directory-gateways.php) is synthetic and covers deferred request parsing.
- [tests/Fixture/Requests/reverse-auth-lookup.php](../tests/Fixture/Requests/reverse-auth-lookup.php) is synthetic and covers deferred request parsing.
- [tests/Fixture/Requests/real-directory-sip-auth-redacted.php](../tests/Fixture/Requests/real-directory-sip-auth-redacted.php) is a real redacted FreeSWITCH Docker `mod_xml_curl` directory `sip_auth` capture.

## Real sip_auth capture

Capture date: `2026-05-09`

Docker environment:

- Local lab evidence: [docs/docker-capture-evidence.md](docker-capture-evidence.md)
- Service: `lab01`
- Container: `freeswitch`
- Network mode: `host`
- Config mount in the local ignored lab: `docker/freeswitch/conf` to `/usr/local/freeswitch/conf`
- Temporary config file in the local ignored lab: `docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml`

The full local `docker/` lab directory is intentionally ignored by git because it contains FreeSWITCH logs, recordings, generated `freeswitch.xml.fsxml`, and local runtime state. The tracked release evidence is the sanitized capture transcript in [docs/docker-capture-evidence.md](docker-capture-evidence.md), plus the redacted request fixture.

FreeSWITCH details:

- Version: `FreeSWITCH Version 1.10.11-release+git~20231222T180831Z~f24064f7c9~64bit`
- `mod_xml_curl` status: `module_exists mod_xml_curl` returned `true`
- Sofia profile: `internal`
- SIP port: `5060`

Capture method:

- A temporary PHP capture endpoint was created outside the repository at `/tmp/freeswitch-xml-curl-capture/index.php`.
- The endpoint listened on `127.0.0.1:18080`, accepted FreeSWITCH POST fields, wrote raw local capture data to `/tmp/freeswitch-xml-curl-capture/latest.raw.json`, wrote redacted capture data to `/tmp/freeswitch-xml-curl-capture/latest.json`, and returned FreeSWITCH not-found XML.
- `xml_curl.conf.xml` was temporarily changed to bind `directory` lookups to `http://127.0.0.1:18080/index.php` with `POST`.
- The Docker service was started with `docker compose up --build -d lab01`.
- FreeSWITCH XML was reloaded and `mod_xml_curl` was reloaded through `fs_cli`.
- A temporary PHP UDP SIP probe outside the repository sent an unauthenticated `REGISTER` to the `internal` Sofia profile, received a `401 Unauthorized` digest challenge, then sent a second `REGISTER` with fake digest credentials for user `1001`.
- The captured request was emitted by FreeSWITCH from `sofia_reg_parse_auth` and included `section=directory`, `action=sip_auth`, `sip_profile=internal`, `sip_auth_username=1001`, and `sip_auth_method=REGISTER`.

Redaction rules:

- The capture harness redacted sensitive fields case-insensitively.
- Exact sensitive keys included `password`, `vm-password`, `reverse-auth-pass`, `sip_auth_response`, `sip_auth_nonce`, `sip_auth_cnonce`, `sip_auth_uri`, `Authorization`, `authorization`, `gateway-credentials`, and `gateway_credentials`.
- Broad sensitive key matching also redacted keys containing `pass`, `password`, `secret`, `token`, `authorization`, `nonce`, `cnonce`, `response`, or `credentials`.
- The committed fixture contains only the redacted scalar FreeSWITCH request fields, preserving real field names and casing.
- No raw capture JSON, live SIP password, digest nonce, digest response, authorization header, gateway credential, or temporary capture script was committed.

Cleanup:

- The temporary `xml_curl.conf.xml` capture binding was restored from `/tmp/xml_curl.conf.xml.before-capture` after capture.
- `xml_curl debug_off` was run after capture.
- The temporary PHP capture server was stopped.

Release status:

The v0.1.0 synthetic-fixture blocker is closed. The release fixture is now based on a real redacted FreeSWITCH Docker `mod_xml_curl` directory `sip_auth` request.
