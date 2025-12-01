| Option                     | Current    | Recommended                   | Why                                  |
| -------------------------- | ---------- | ----------------------------- | ------------------------------------ |
| `authnRequestsSigned`      | false      | true                          | AuthNRequests signed by SP for trust |
| `wantAssertionsSigned`     | true       | true                          | Mandatory for integrity              |
| `wantMessagesSigned`       | false      | true                          | Extra integrity for messages         |
| `saml.assertion.signature` | true       | true                          | SP verifies IdP signature            |
| `saml.server.signature`    | true       | true                          | IdP signs response                   |
| `x509cert` / `privateKey`  | empty      | Provide long-lived SP keypair | Required for signing requests        |
| `NameIDFormat`             | persistent | persistent                    | Avoids leaking PII                   |
| `saml.force.post.binding`  | true       | true                          | POST more secure than redirect       |
| `saml.encrypt`             | false      | true if supported             | Encrypt attributes                   |
| `singleLogoutService`      | defined    | signed requests/responses     | Ensure full logout propagation       |

SAML settings to update to better mimic production.
