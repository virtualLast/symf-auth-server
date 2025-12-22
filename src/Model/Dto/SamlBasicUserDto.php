<?php

namespace App\Model\Dto;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class SamlBasicUserDto implements ResourceOwnerInterface
{
    private string $id;

    /**
     * Returns the identifier of the authorized resource owner.
     *
     * @return mixed
     */
    public function getId(): string
    {
        return $this->id;
    }

    public function getSub(): string
    {
        return $this->id;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sub' => $this->id
        ];
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }
}
