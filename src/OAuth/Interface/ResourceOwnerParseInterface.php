<?php

namespace App\OAuth\Interface;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

interface ResourceOwnerParseInterface
{
    public function parse(ResourceOwnerInterface $resourceOwner): array;
}
