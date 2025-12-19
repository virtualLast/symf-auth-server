<?php

namespace App\OAuth\Parse;

use App\OAuth\Interface\ResourceOwnerParseInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class DefaultResourceOwnerParser implements ResourceOwnerParseInterface
{

    // nothing to do so return empty array
    public function parse(ResourceOwnerInterface $resourceOwner): array
    {
        return [];
    }
}
