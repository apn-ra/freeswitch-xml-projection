# FreeSWITCH `xml_curl.conf.xml`

Example provider-local binding:

```xml
<configuration name="xml_curl.conf" description="cURL XML Gateway">
  <bindings>
    <binding name="directory">
      <param name="gateway-url" value="https://apntalk.example.test/freeswitch/xml-curl"/>
      <param name="method" value="post"/>
      <param name="credentials" value="gateway-user:gateway-password"/>
      <param name="auth-scheme" value="basic"/>
      <param name="timeout" value="5"/>
      <param name="enable-cacert-check" value="true"/>
      <param name="ssl-verifyhost" value="true"/>
      <param name="response-max-bytes" value="4096"/>
      <param name="debug_on" value="false"/>
    </binding>
  </bindings>
</configuration>
```

Notes:

- Use HTTPS.
- Use POST.
- Store gateway credentials outside this package.
- Keep certificate verification enabled.
- Keep `response-max-bytes` bounded.
- Enable `xml_curl debug_on` only in controlled test environments because request and response payloads can expose sensitive data.
