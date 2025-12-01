<?php
return [
    'strict' => false,   // relax checks for local dev
    'debug' => true,     // useful to debug SAML requests

    'sp' => [
        'entityId' => 'http://localhost:8081/realms/local-dev',  // MUST match Keycloak client entityID
        'assertionConsumerService' => [
            'url' => 'http://localhost:8000/saml/acs',          // Symfony route for ACS
        ],
        'singleLogoutService' => [
            'url' => 'http://localhost:8000/saml/logout',       // optional for now
        ],
        'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
        'x509cert' => 'MIID+TCCAuGgAwIBAgIUHn/kWSCYYga/hHP1xsQNqtDl470wDQYJKoZIhvcNAQELBQAwgYsxCzAJBgNVBAYTAlVLMQ4wDAYDVQQIDAVEZXZvbjEPMA0GA1UEBwwGRXhldGVyMRIwEAYDVQQKDAlsb2NhbGhvc3QxEjAQBgNVBAsMCWxvY2FsaG9zdDESMBAGA1UEAwwJbG9jYWxob3N0MR8wHQYJKoZIhvcNAQkBFhBtamxhc3RAZ21haWwuY29tMB4XDTI1MTIwMTA4NDczOFoXDTI2MTIwMTA4NDczOFowgYsxCzAJBgNVBAYTAlVLMQ4wDAYDVQQIDAVEZXZvbjEPMA0GA1UEBwwGRXhldGVyMRIwEAYDVQQKDAlsb2NhbGhvc3QxEjAQBgNVBAsMCWxvY2FsaG9zdDESMBAGA1UEAwwJbG9jYWxob3N0MR8wHQYJKoZIhvcNAQkBFhBtamxhc3RAZ21haWwuY29tMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAhTrJRdmsCjDfT1xqqD8hEHwuM0EMgUTpbzdwckHU0yubBUPJrc2e2MtxZRODiG76q69DoxNJmJkdjmamqUS4L4NSWRrjvDGG5MozB/JgY+8XrWcLW6cMTuX/MWscFdZMH1ykEIXXDRWdN2PAZ7L9PKWVaizHFCov0pr9jvmLLKDvFmMYlV8aRa2fvblg5FShNB8jIwnnzdSQ0f4mX5U6buOfFlbKI/064iOWQZmMcSeMURM/t+PnCs6kmaU++sKB2qanVpciye5+2/Eke8OUXVE+SBfeTA1cW4gFGQ6vIS6+QW44uFR2kTRL1du7IYIbHFR9dDrOfN39KqExItX3bwIDAQABo1MwUTAdBgNVHQ4EFgQUwQS+zOTcRPRHQqSvYv0+B4ugBWYwHwYDVR0jBBgwFoAUwQS+zOTcRPRHQqSvYv0+B4ugBWYwDwYDVR0TAQH/BAUwAwEB/zANBgkqhkiG9w0BAQsFAAOCAQEAGeOf2m61R0ne+PxKLkUK7OgiaW5RTbZLkywm/Ry4cWL7DpJTrZhMEpgpxsffnE7+Vgd8GK+0Lxz0gddvpoZ+nM+D1Hc6uACdtszAfGu7CnavrWbS1lME958nEwOXWv1Aizh5oy/+uWIydvpdFSkjH43Vc6XGYDPxYW+aNrSm6YAnFbVE00oZLOcfyiqgnLMjUh57YECQJwfBrJJ3BQ4isKRuUsVLV98BotGosFInnW3ed3BU2EISCtAoPQvuuI+hZHKVID/G39zpJS9xFnv1Rqlw/qd1lbKwYwXCNCuviVTV0jERj9TRfL5LXkIVfBF3ifQYPBpIpzdUvZi2hsb2SQ==',  // optional unless signing requests
        'privateKey' => 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCFOslF2awKMN9PXGqoPyEQfC4zQQyBROlvN3ByQdTTK5sFQ8mtzZ7Yy3FlE4OIbvqrr0OjE0mYmR2OZqapRLgvg1JZGuO8MYbkyjMH8mBj7xetZwtbpwxO5f8xaxwV1kwfXKQQhdcNFZ03Y8Bnsv08pZVqLMcUKi/Smv2O+YssoO8WYxiVXxpFrZ+9uWDkVKE0HyMjCefN1JDR/iZflTpu458WVsoj/TriI5ZBmYxxJ4xREz+34+cKzqSZpT76woHapqdWlyLJ7n7b8SR7w5RdUT5IF95MDVxbiAUZDq8hLr5Bbji4VHaRNEvV27shghscVH10Os583f0qoTEi1fdvAgMBAAECggEANvwRiEXzNkuARikOjbxsIXkWuil9BzbRHojjXAxmUPa8ploZOKVViwS/mmcI0Hx48PVG9V0m8Rc0NwqPeul1GIraqBKsbYWFNhRrJjr2ZBgPjg2qhtt0/XT3ssYRU8PbK9BYl8cc/3Xtqif9hu68i9SMy0KKsPxOO12jCuhbc4GpZ0NpDGUGJPfMi4aFRKoLMBiAgRTyYvgy9N0BSV4UqTF8EPtY8kZqNrXA8G2ieId9XgkfCdcVet7pHonqFZzt+bYeDZ54bjG+645VybmRWDqRQMwY3JlYjHRjzk8QYZtlq4dXLiuPsnq44/lQ5TJcT+rkLuy5rD/mt1E9ALz6GQKBgQC7DNHXpkOcAtnbEkWGb1upf895GJ/RcQnPrR3pIbZKFsRSm6T9GDVGsKjB6+myU3gFYKbAQJgmiYz+5UqD7DDI1MWGCNNBUTDcq7ipeGy7I+Akn+O7Gdj6OWdtF3mNHWGcJvhKh7K8C3DMVl++6qGAPvnl20KFHZcjbZzSvb7++QKBgQC2VySVwUOIp0wDmSutL1UDtFVDAEJUpE/rrHXCKr5iYO7BgyDmWpO8jGFeHM6s2U6vFrbCHiGdwou+KEnvJq0PomEnU238qGY0sQ2xBgYeG0rblU782iuWz8xOlyDLSq5HrLmutFsyrGUnYh9Zc8kt7TNoJbjj55pNYd8Fr5x7pwKBgGLzBCsNVGS6iV6/irH0RMwkxa9s3Faicqs/DDygzdrhsld06NHOtZhAqfV0BDuGtk4589xuD08Lot/Qkhu5nZEQJbGjB4ZdGfkSimx0PSi+cLtPLdxzbvnn1hO6wF1rNpCxeNWMdXvOnYwrARlw66B5MB9tXwImRibCvJwrLleZAoGAfqdVIDhYAonCQRWZwvgHo0UVnC6H5GclpFvsh+xMxeTysrO0nIz55ZPU5hw4atOe1ft/AqMPUpeZnYI6qMVCnIN73GcVFNSb+amqCvZWNX9bpd62OOgKMv0vur865jp/iAWwDGv2lOIueQatI+NLMH18obDiFlv/UBTVZwuuNC0CgYBZjdTKxzuN44WasTIzhoPToiXj4tcJckhAYwh6alb5HRpQtnLpoV5XTG2X7YAsZSmap0jt3RY/X1+vDRlHrBMsvCUy31HIQy0dQpWL7ZME5Pg2lvx1ZwURVHS39R2fgN7KuCrbG9GpSR+LAYM4h2tWvJndbVT+0hA0OS4ZjQaKZA==', // optional unless signing requests
    ],

    'idp' => [
        'entityId' => 'http://localhost:8081/realms/local-dev',
        'singleSignOnService' => [
            'url' => 'http://localhost:8081/realms/local-dev/protocol/saml', // use POST binding
        ],
        'singleLogoutService' => [
            'url' => 'http://localhost:8081/realms/local-dev/protocol/saml', // POST binding
        ],
        'metadata_url' => 'http://localhost:8081/realms/local-dev/protocol/saml/descriptor',
        'x509cert' => 'MIICoTCCAYkCBgGawKtNlDANBgkqhkiG9w0BAQsFADAUMRIwEAYDVQQDDAlsb2NhbC1kZXYwHhcNMjUxMTI2MTQ1NTQzWhcNMzUxMTI2MTQ1NzIzWjAUMRIwEAYDVQQDDAlsb2NhbC1kZXYwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDEFjxPdsr5nT83qqE5hZCwlwpzJFmAUVaLCneISDWGV6uHn7ueEb+FUvcnZFD9+Yap4Svr3souzcSEJ3zFseLHeGq2ha76g7yR+P99O9uB/saRKe/K2wmJbheV1/SbsNPEjA/7XKXZ2+54rwUZ5RAfWGkEcy5Ackw6uyUmqav6FE9KS2rFYKg97POM7xHyG/sUmH24lj6okspG2/0cM7mOLLmKcI3Q/eQjo8WHWV0QaWBpmQ7zJJYq0u6OKSmPxD2MPXckCWfPESszv/O0rHUmKBq9LQy9t+4qdb3p6fIllLjo7jo2ID2otvyPWB75iIzw5XqxlzNxmp7xNbunCRW5AgMBAAEwDQYJKoZIhvcNAQELBQADggEBAKZq5vznLphV6jETamGKIEjpnQM7qqKqLKXJzMTtE8FfEWG20QHFqpJVgKyQbTZIlzw0SbzzNf1ii1WUaZPsuYCd2MEda762vLMRlVHifp/cADLc2O7/D0klp9dF9AkyDXc7pkoXp7NF3+eK6TxJrvRoMuZ6I1mBSl5ydlCqzmXv/xSt/LXCOu+VzRnzBr9ltaxZQZNMzBR3iOcyOzA8DRBq7SbzsoX/ojygzHg3cTy07/oyTgszaTI+5KUy6dL3IaFYbwp35tz3kZhfBqd8AuB4fyPfxNIDFJMRoGenCJ7qj2Bi9CtQjT48j2GOkpCWE/n7h2H+4AcIlwwSGBzV38s=',
    ],

    'security' => [
        'authnRequestsSigned' => true,  // SP does NOT sign AuthNRequests
        'wantAssertionsSigned' => true,  // SP WILL expect Keycloak-signed assertions
        'wantMessagesSigned' => false,   // optional; usually false for POST binding
    ],

];
