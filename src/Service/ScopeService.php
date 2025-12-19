<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\Enum\ProviderEnum;

class ScopeService
{
    /**
     * Returns the OIDC scopes to request for a given provider.
     *
     * @return array<string>
     */
    public function getScopesForProvider(ProviderEnum $provider): array
    {
        $baseScopes = ['openid', 'profile', 'email'];

        return match ($provider) {
            ProviderEnum::KEYCLOAK_TESCO => [...$baseScopes, 'params'],
            default => $baseScopes,
        };
    }
}


