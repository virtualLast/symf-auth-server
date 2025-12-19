<?php

namespace App\Mapper;

use App\Model\Dto\AccessRolesDto;
use App\Model\Dto\ResourceOwnerDto;
use App\Model\Enum\ProviderEnum;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

final class ResourceOwnerMapper
{
    public function map(
        ResourceOwnerInterface $resourceOwner,
        ProviderEnum $provider,
        ?AccessRolesDto $accessRoles = null
    ): ResourceOwnerDto {
        $data = $resourceOwner->toArray();

        if (!isset($data['sub'])) {
            throw new \RuntimeException(
                sprintf('OIDC provider "%s" did not return a "sub" claim', $provider->value)
            );
        }

        return new ResourceOwnerDto(
            provider: $provider,
            tokenSub: $data['sub'],
            email: $data['email'] ?? null,
            accessLevels: $accessRoles?->accessLevels ?? [],
            userRoles: $accessRoles?->hierCodes ?? []
        );
    }
}
