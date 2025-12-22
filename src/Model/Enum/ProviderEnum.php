<?php

namespace App\Model\Enum;

enum ProviderEnum: string
{
    case KEYCLOAK_LOCAL = 'keycloak_local';
    case KEYCLOAK_TESCO = 'keycloak_tesco';
    case KEYCLOAK_SAML = 'keycloak_saml';
    case AUTH0    = 'auth0';
    case OKTA     = 'okta';
}
