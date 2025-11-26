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
        'x509cert' => '',  // optional unless signing requests
        'privateKey' => '', // optional unless signing requests
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
        'authnRequestsSigned' => false,  // SP does NOT sign AuthNRequests
        'wantAssertionsSigned' => true,  // SP WILL expect Keycloak-signed assertions
        'wantMessagesSigned' => false,   // optional; usually false for POST binding
    ],

];
