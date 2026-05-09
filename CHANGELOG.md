# Changelog

## Unreleased

- Bootstrap `apntalk/freeswitch-xml-projection` as a pure Composer library.
- Add `mod_xml_curl` request parsing, redaction, directory DTOs, deterministic XML rendering, and not-found responses for the v0.1 directory `sip_auth` slice.
- Add fixture-backed tests, CI, examples, and boundary/security documentation.
- Replace the synthetic `sip_auth` request fixture with a real redacted FreeSWITCH Docker `mod_xml_curl` directory capture from `2026-05-09`.
- Document fixture provenance, Docker service/container details, SIP trigger method, and redaction rules.
