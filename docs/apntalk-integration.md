# APNTalk integration

APNTalk is the canonical authority.

This package must not:

- resolve tenants
- load SIP accounts
- fetch or decrypt credentials
- authorize FreeSWITCH
- enforce HTTP-edge controls
- write to a database
- emit logs or metrics

APNTalk should:

1. Parse request fields with `XmlCurlRequestParser`.
2. Decide whether the request is supported in the current release.
3. Resolve endpoint and credential material in APNTalk core.
4. Map that data into `DirectoryDocument` and related DTOs.
5. Return `XmlCurlResponse::xml(...)` or `XmlCurlResponse::notFound()`.

Unsupported but well-formed requests such as reverse-auth lookup, gateways, and network-list should return the package's not-found XML in `v0.1.x`.
