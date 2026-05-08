# Changelog

## Unreleased

- Bootstrap `apntalk/freeswitch-xml-projection` as a pure Composer library.
- Add `mod_xml_curl` request parsing, redaction, directory DTOs, deterministic XML rendering, and not-found responses for the v0.1 directory `sip_auth` slice.
- Add fixture-backed tests, CI, examples, and boundary/security documentation.
- Release-readiness audit on `2026-05-08` found no real local redacted FreeSWITCH `sip_auth` capture.
- Release blocker: do not tag `v0.1.0` until the synthetic redacted request fixture is replaced with, or verified against, a real redacted FreeSWITCH `sip_auth` capture.
