# Docker capture evidence

This document records the sanitized local Docker lab evidence used for the real `sip_auth` fixture capture. It is tracked because the full local `docker/` lab directory is ignored to avoid committing FreeSWITCH logs, recordings, generated runtime state, and local configuration material.

## Local lab snapshot

Capture date: `2026-05-09`

The local ignored Docker lab used for capture had this safe shape:

```yaml
services:
  lab01:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: freeswitch
    network_mode: host
    restart: unless-stopped
    volumes:
      - ./freeswitch/logs:/usr/local/freeswitch/log
      - ./freeswitch/recordings:/usr/local/freeswitch/recordings
      - ./freeswitch/conf:/usr/local/freeswitch/conf
```

The local `Dockerfile` built FreeSWITCH from `signalwire/freeswitch` tag `v1.10.11`, included `xml_int/mod_xml_curl` in `build/modules.conf.in`, and patched `autoload_configs/modules.conf.xml` so `mod_xml_curl` was loaded. Its generated `xml_curl.conf.xml` placeholder contained no active gateway binding and no live credentials.

## Runtime checks

The running Docker service was inspected with non-mutating commands:

```text
docker ps
```

showed container `freeswitch` running.

```text
docker exec freeswitch fs_cli -p [redacted] -x version
```

returned:

```text
FreeSWITCH Version 1.10.11-release+git~20231222T180831Z~f24064f7c9~64bit
```

```text
docker exec freeswitch fs_cli -p [redacted] -x 'module_exists mod_xml_curl'
```

returned:

```text
true
```

## Capture method

A temporary PHP capture endpoint was created outside the repository at `/tmp/freeswitch-xml-curl-capture/index.php`. It listened on `127.0.0.1:18080`, accepted FreeSWITCH request fields, wrote raw local capture data only under `/tmp`, wrote redacted capture data under `/tmp`, and returned FreeSWITCH not-found XML.

The local ignored `xml_curl.conf.xml` was temporarily changed to bind `directory` lookups to that endpoint with `POST`. After capture, the file was restored and no Docker config diff was left in the repository.

A temporary PHP UDP SIP probe outside the repository sent a `REGISTER` to the `internal` Sofia profile, received a digest challenge, and sent a second `REGISTER` with fake credentials for user `1001`. The captured request was emitted by FreeSWITCH from `sofia_reg_parse_auth` and included `section=directory`, `action=sip_auth`, `sip_profile=internal`, `sip_auth_username=1001`, and `sip_auth_method=REGISTER`.

## Redaction and repository safety

The capture harness redacted sensitive fields case-insensitively. Exact keys included `password`, `vm-password`, `reverse-auth-pass`, `sip_auth_response`, `sip_auth_nonce`, `sip_auth_cnonce`, `sip_auth_uri`, `Authorization`, `authorization`, `gateway-credentials`, and `gateway_credentials`.

Broad matching also redacted keys containing `pass`, `password`, `secret`, `token`, `authorization`, `nonce`, `cnonce`, `response`, or `credentials`.

No raw capture JSON, SIP password, digest nonce, digest response, authorization header, gateway credential, FreeSWITCH log, recording, generated `freeswitch.xml.fsxml`, or temporary capture script was committed.
