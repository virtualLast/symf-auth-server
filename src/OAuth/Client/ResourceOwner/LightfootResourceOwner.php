<?php

namespace App\OAuth\Client\ResourceOwner;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class LightfootResourceOwner implements ResourceOwnerInterface
{

    protected array $response;

    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->response['sub'];
    }

    public function getEmail()
    {
        if (array_key_exists('email', $this->response)) {
            return $this->response['email'];
        }
        return null;
    }

    /**
     * Get preferred display name.
     */
    public function getName(): ?string
    {
        if (array_key_exists('name', $this->response)) {
            return $this->response['name'];
        }
        return null;
    }

    /**
     * Get preferred first name.
     */
    public function getFirstName(): ?string
    {
        if (array_key_exists('given_name', $this->response)) {
            return $this->response['given_name'];
        }
        return null;
    }

    /**
     * Get preferred last name.
     */
    public function getLastName(): ?string
    {
        if (array_key_exists('family_name', $this->response)) {
            return $this->response['family_name'];
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return $this->response;
    }
}
