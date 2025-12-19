<?php

namespace App\OAuth\Factory;

use App\Model\Enum\ProviderEnum;
use App\OAuth\Interface\ResourceOwnerParseInterface;
use App\OAuth\Parse\TescoResourceOwnerParser;
use App\OAuth\Parse\DefaultResourceOwnerParser;

class TokenParseFactory
{
    public function getParser(ProviderEnum $provider): ResourceOwnerParseInterface
    {
        return match ($provider) {
            ProviderEnum::KEYCLOAK_TESCO => new TescoResourceOwnerParser(),
            default => new DefaultResourceOwnerParser(),
        };
    }
}
