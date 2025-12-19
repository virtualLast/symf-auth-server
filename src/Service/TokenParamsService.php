<?php

namespace App\Service;

use App\Model\Dto\AccessRolesDto;
use App\Model\Enum\ProviderEnum;
use App\OAuth\Exception\OauthParseException;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class TokenParamsService
{


    /**
     * @throws OauthParseException
     */
    public function parse(ResourceOwnerInterface $resourceOwner, ProviderEnum $provider): ?AccessRolesDto
    {
        if ($provider !== ProviderEnum::KEYCLOAK_TESCO) {
            return null;
        }

        $data = $resourceOwner->toArray();

        if (!isset($data['params']) || !is_array($data['params'])) {
            throw new OauthParseException('Invalid or missing params in resource owner');
        }

        $params = $data['params'];

        if (!isset($params['AccessLevel']) || !is_array($params['AccessLevel'])) {
            throw new OauthParseException('Malformed params: AccessLevel is required');
        }

        return new AccessRolesDto(
            $params['AccessLevel'],
            $params['HierCode'] ?? []
        );
    }
}
