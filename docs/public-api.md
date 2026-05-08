# Public API

`v0.1.0` exposes only the package-owned parser, DTO, renderer, response, enum, exception, and redaction surfaces required for directory `sip_auth`.

## HTTP

- `APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequest`
- `APNTalk\FreeSwitchXmlProjection\Http\XmlCurlRequestParser`
- `APNTalk\FreeSwitchXmlProjection\Http\XmlCurlResponse`

## Directory

- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDocument`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryDomain`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryUser`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryParam`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryVariable`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryCredential`
- `APNTalk\FreeSwitchXmlProjection\Directory\PlainPasswordCredential`
- `APNTalk\FreeSwitchXmlProjection\Directory\A1HashCredential`
- `APNTalk\FreeSwitchXmlProjection\Directory\DirectoryXmlRenderer`

## Result

- `APNTalk\FreeSwitchXmlProjection\Result\ResultXmlRenderer`

## Security

- `APNTalk\FreeSwitchXmlProjection\Security\Redactor`
- `APNTalk\FreeSwitchXmlProjection\Security\SensitiveFieldList`

## Enums

- `APNTalk\FreeSwitchXmlProjection\Enum\XmlCurlSection`
- `APNTalk\FreeSwitchXmlProjection\Enum\DirectoryAction`
- `APNTalk\FreeSwitchXmlProjection\Enum\DirectoryPurpose`
- `APNTalk\FreeSwitchXmlProjection\Enum\CredentialMode`

## Exceptions

- `APNTalk\FreeSwitchXmlProjection\Exception\InvalidProjectionException`
- `APNTalk\FreeSwitchXmlProjection\Exception\InvalidXmlCurlRequestException`
- `APNTalk\FreeSwitchXmlProjection\Exception\XmlRenderingException`

Helpers under `src/Internal/` are internal and excluded from the public API contract.
