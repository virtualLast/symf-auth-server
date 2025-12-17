<?php

namespace App\Model\Dto;

use App\Model\Enum\ProviderEnum;

final readonly class ResourceOwnerDto
{
    public function __construct(
        public ProviderEnum $provider,
        public string $tokenSub,
        public ?string $email,
    ) {}
}
