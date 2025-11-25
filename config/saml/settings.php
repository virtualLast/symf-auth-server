<?php


/**
 * Full SAML configuration for OneLogin PHP-SAML.
 * All options are documented so you know exactly what each field does.
 */

return [

    /**
     * strict = true enforces:
     * - Valid signatures
     * - Strict XML parsing
     * - Proper destination URLs
     * - Valid audiences
     *
     * For local development, strict = false makes life easier
     * by relaxing many SAML protocol constraints.
     */
    'strict' => false,

    /**
     * debug = true prints useful information in error cases.
     * Keep this enabled for development only.
     */
    'debug' => true,

    /**
     * Service Provider (your Symfony app) configuration.
     * These values describe YOUR application to Keycloak.
     */
    'sp' => [
        /**
         * The unique identifier of your SP.
         * Keycloak sees this as the SP's "entityId".
         *
         * Best practice: use your metadata URL.
         */
        'entityId' => 'http://localhost:8000/saml/metadata',

        /**
         * Assertion Consumer Service (ACS) is the endpoint
         * where Keycloak POSTs the SAML Assertion after login.
         */
        'assertionConsumerService' => [
            'url' => 'http://localhost:8000/saml/acs',
        ],

        /**
         * Single Logout Service (SLO) endpoint.
         * Optional — you can implement SLO later.
         */
        'singleLogoutService' => [
            'url' => 'http://localhost:8000/saml/logout',
        ],

        /**
         * The SP certificate (optional for dev)
         *
         * If you want to sign AuthN requests or decrypt encrypted assertions,
         * you must supply your SP certificate and private key.
         */
        'x509cert' => '',

        /**
         * The private key that matches the SP certificate.
         * Also optional unless you enable signing or encryption.
         */
        'privateKey' => '',
    ],

    /**
     * Identity Provider (Keycloak) configuration.
     * This tells your SP how to communicate WITH keycloak.
     */
    'idp' => [
        /**
         * Keycloak realm ID as an Entity ID.
         * You can get this from:
         * Realm → Endpoints → SAML 2.0 Identity Provider Metadata
         */
        'entityId' => 'http://localhost:8081/realms/local-dev',

        /**
         * Login endpoint for SAML SSO.
         * Keycloak SSO endpoint always ends with `/protocol/saml`.
         */
        'singleSignOnService' => [
            'url' => 'http://localhost:8081/realms/local-dev/protocol/saml',
        ],

        /**
         * Logout endpoint — optional until you implement SLO.
         */
        'singleLogoutService' => [
            'url' => 'http://localhost:8081/realms/local-dev/protocol/saml',
        ],

        /**
         * Keycloak's signing certificate.
         *
         * REQUIRED if strict = true.
         *
         * You can copy it from:
         * Keycloak → Realm → Keys → SAML Keys
         *
         * This allows the SP to verify that the assertion was actually
         * generated and signed by Keycloak and not tampered with.
         */
        'x509cert' => '',
    ],
];
