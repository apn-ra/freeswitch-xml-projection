# Stability policy

- `v0.1.x` is the first stable public API for directory `sip_auth` projection only.
- Public classes listed in [public-api.md](public-api.md) are intended to remain source-compatible within `v0.1.x` except for bug-fix tightening around invalid input.
- XML fixture output is part of the package contract for the documented response shapes.
- Internal helpers, examples, and docs are not covered by the same compatibility promise.
- Deferred features such as reverse-auth responses, message-count responses, gateways, and network-list rendering may add new APIs in later minor releases.
