# Directory contract

`v0.1.0` renders FreeSWITCH directory XML only for `sip_auth` responses.

## Request handling

- Unknown scalar request fields are preserved in `XmlCurlRequest::raw()`.
- Sensitive raw fields can be accessed safely through `XmlCurlRequest::redacted()`.
- Unknown sections, actions, and purposes do not crash parsing; typed accessors return `null`.
- `action` takes precedence over `Action`.
- `user` takes precedence over `sip_auth_username`.
- `domain` takes precedence over `sip_auth_realm`.

## Rendering

- XML declaration is always emitted.
- The document root is always `<document type="freeswitch/xml">`.
- Directory responses always render `<section name="directory">`.
- Domain order, user order, param order, and variable order are deterministic.
- Credential params render before caller-supplied user params.
- Caller param and variable order is otherwise preserved.

## Validation

- Empty documents are rejected.
- Domain names and user IDs must be non-empty and at most 255 characters.
- Param and variable names must be non-empty and at most 128 characters.
- Invalid XML control characters are rejected before rendering.
- `cacheable=false` is treated as non-rendered.
- User `type` may be `null` or `pointer`.
- A rendered auth domain must contain at least one user.
