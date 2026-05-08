# Release checklist

- `composer validate --strict`
- `composer install --prefer-dist --no-progress`
- `composer check`
- Confirm whether a real redacted FreeSWITCH `mod_xml_curl` directory `sip_auth` capture is present locally. The `2026-05-08` audit did not find one.
- Verify [tests/Fixture/Requests/real-directory-sip-auth-redacted.php](/home/ramjf/projects/freeswitch-xml-projection/tests/Fixture/Requests/real-directory-sip-auth-redacted.php) is replaced with, or explicitly confirmed against, a real redacted FreeSWITCH `sip_auth` capture.
- Confirm docs still state that APNTalk owns authority and that this package owns only XML projection.
- Confirm no live credentials appear in fixtures, examples, tests, or docs.
- Confirm no reverse-auth, message-count, gateway XML, dialplan, or Laravel runtime coupling was added.

Do not tag `v0.1.0` while the synthetic fixture provenance blocker remains open.
