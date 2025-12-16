<?php

namespace App\Model\Enum;

enum ProviderEnum: string
{
    case KEYCLOAK = 'keycloak';
    case AUTH0    = 'auth0';
    case OKTA     = 'okta';
}
