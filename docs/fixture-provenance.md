# Fixture provenance

- [tests/Fixture/Requests/directory-sip-auth-minimal.php](/home/ramjf/projects/freeswitch-xml-projection/tests/Fixture/Requests/directory-sip-auth-minimal.php) is synthetic and intentionally minimal.
- [tests/Fixture/Requests/directory-gateways.php](/home/ramjf/projects/freeswitch-xml-projection/tests/Fixture/Requests/directory-gateways.php) is synthetic and covers deferred request parsing.
- [tests/Fixture/Requests/reverse-auth-lookup.php](/home/ramjf/projects/freeswitch-xml-projection/tests/Fixture/Requests/reverse-auth-lookup.php) is synthetic and covers deferred request parsing.
- [tests/Fixture/Requests/real-directory-sip-auth-redacted.php](/home/ramjf/projects/freeswitch-xml-projection/tests/Fixture/Requests/real-directory-sip-auth-redacted.php) is synthetic and safe, but not yet confirmed against a real redacted capture.

Audit status:

- On `2026-05-08`, the release-readiness audit did not find any real redacted FreeSWITCH `mod_xml_curl` directory `sip_auth` capture in the repository or local workspace.
- The current fixture remains a synthetic FreeSWITCH-like request shape intended only to exercise parser aliases, preserved scalar fields, and redaction behavior without committing secrets.

Release blocker:

Do not tag `v0.1.0` until the real-like redacted `sip_auth` fixture is replaced with, or explicitly verified against, a real redacted FreeSWITCH request capture.
