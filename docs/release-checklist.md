# Release checklist

- `composer validate --strict`
- `composer install --prefer-dist --no-progress`
- `composer check`
- Confirm [tests/Fixture/Requests/real-directory-sip-auth-redacted.php](../tests/Fixture/Requests/real-directory-sip-auth-redacted.php) remains based on the real redacted FreeSWITCH Docker `mod_xml_curl` directory `sip_auth` capture documented in [docs/fixture-provenance.md](fixture-provenance.md).
- Confirm docs still state that APNTalk owns authority and that this package owns only XML projection.
- Confirm no live credentials appear in fixtures, examples, tests, or docs.
- Confirm no temporary Docker capture config is committed under `docker/freeswitch/conf/autoload_configs/xml_curl.conf.xml`.
- Confirm no reverse-auth, message-count, gateway XML, dialplan, or Laravel runtime coupling was added.

The v0.1.0 synthetic-fixture provenance blocker is closed as of `2026-05-09`.
