<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use App\Trait\CreatedUpdatedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
#[ORM\Table(name: '`token`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_REFRESH_TOKEN', fields: ['refreshToken'])]
#[ORM\HasLifecycleCallbacks]
class Token
{
    use CreatedUpdatedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $tokenSub = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $refreshToken = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $accessToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiry = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $revoked = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTokenSub(): ?User
    {
        return $this->tokenSub;
    }

    public function setTokenSub(?User $tokenSub): static
    {
        $this->tokenSub = $tokenSub;

        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    public function getExpiry(): ?\DateTimeImmutable
    {
        return $this->expiry;
    }

    public function setExpiry(?\DateTimeImmutable $expiry): static
    {
        $this->expiry = $expiry;

        return $this;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function setRevoked(bool $revoked): static
    {
        $this->revoked = $revoked;

        return $this;
    }
}
