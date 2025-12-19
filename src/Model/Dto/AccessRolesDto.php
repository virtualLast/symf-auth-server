<?php

namespace App\Model\Dto;

final readonly class AccessRolesDto
{
    public function __construct(
        public array $accessLevels,
        public array $hierCodes
    ) {
    }
}
