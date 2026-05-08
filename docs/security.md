# Security

- Prefer `A1HashCredential` over `PlainPasswordCredential`.
- Never log rendered XML with live credentials.
- Never log raw request arrays without redaction.
- Treat `password`, `vm-password`, `reverse-auth-pass`, `sip_auth_response`, `sip_auth_nonce`, `sip_auth_cnonce`, `sip_auth_uri`, `Authorization`, `authorization`, `gateway-credentials`, and `gateway_credentials` as sensitive.
- Keep basic auth, mTLS, IP allowlists, rate limits, audit logging, and alerting at APNTalk's HTTP edge.
- Do not commit live tenant domains, production identifiers, or live gateway secrets.

This package does not implement credential storage, credential rotation, reverse-auth responses, or HTTP authentication middleware.
