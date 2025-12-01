| Option                     | Current    | Recommended                   | Why                                  |
| -------------------------- | ---------- | ----------------------------- | ------------------------------------ |
| `wantMessagesSigned`       | false      | true                          | Extra integrity for messages         |
| `saml.encrypt`             | false      | true if supported             | Encrypt attributes                   |
| `singleLogoutService`      | defined    | signed requests/responses     | Ensure full logout propagation       |

SAML settings to update to better mimic production.
