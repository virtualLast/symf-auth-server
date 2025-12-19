<?php

namespace App\OAuth\Parse;

use App\OAuth\Interface\ResourceOwnerParseInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class TescoResourceOwnerParser implements ResourceOwnerParseInterface
{

    public function parse(ResourceOwnerInterface $resourceOwner): array
    {
        $data = $resourceOwner->toArray();
        if(isset($data['params']) && count($data['params']) > 0) {
            return [
                'params' => [
                    'AccessLevel' => $params['AccessLevel'] ?? [],
                    'HierCode' => $params['HierCode'] ?? [],
                ]
            ];
        }
        return [];
    }
}
