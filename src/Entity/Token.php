<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use App\Trait\CreatedUpdatedTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
#[ORM\Table(name: '`token`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_LOCAL_REFRESH', fields: ['localRefreshToken'])]
#[ORM\HasLifecycleCallbacks]
class Token
{
    use CreatedUpdatedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'tokens')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?User $user = null;

    // -------------------------------------
    // LOCAL OPAQUE TOKENS (your own system)
    // -------------------------------------

    #[ORM\Column(type: Types::STRING, length: 255)]
    private string $localAccessToken;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private string $localRefreshToken;

    private ?string $rawLocalRefreshToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $localAccessTokenExpiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $localRefreshTokenExpiresAt = null;

    // -------------------------
    // STORED KEYCLOAK TOKENS
    // -------------------------

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $idpAccessToken = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $idpRefreshToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $idpAccessTokenExpiresAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $idpRefreshTokenExpiresAt = null;

    // -------------------------------------
    // Revocation / Session state
    // -------------------------------------

    #[ORM\Column(options: ['default' => false])]
    private bool $revoked = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    // Local Access Token
    public function getLocalAccessToken(): string
    {
        return $this->localAccessToken;
    }

    public function setLocalAccessToken(string $token): static
    {
        $this->localAccessToken = $token;
        return $this;
    }

    public function getLocalRefreshToken(): string
    {
        return $this->localRefreshToken;
    }

    public function setLocalRefreshToken(string $token): static
    {
        $this->localRefreshToken = $token;
        return $this;
    }

    public function getRawLocalRefreshToken(): ?string
    {
        return $this->rawLocalRefreshToken;
    }

    public function setRawLocalRefreshToken(string $token): static
    {
        $this->rawLocalRefreshToken = $token;
        return $this;
    }

    public function getLocalAccessTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->localAccessTokenExpiresAt;
    }

    public function setLocalAccessTokenExpiresAt(?\DateTimeImmutable $expiry): static
    {
        $this->localAccessTokenExpiresAt = $expiry;
        return $this;
    }

    public function getLocalRefreshTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->localRefreshTokenExpiresAt;
    }

    public function setLocalRefreshTokenExpiresAt(?\DateTimeImmutable $expiry): static
    {
        $this->localRefreshTokenExpiresAt = $expiry;
        return $this;
    }

    // IDP / Keycloak Tokens

    public function getIdpAccessToken(): ?string
    {
        return $this->idpAccessToken;
    }

    public function setIdpAccessToken(?string $token): static
    {
        $this->idpAccessToken = $token;
        return $this;
    }

    public function getIdpRefreshToken(): ?string
    {
        return $this->idpRefreshToken;
    }

    public function setIdpRefreshToken(?string $token): static
    {
        $this->idpRefreshToken = $token;
        return $this;
    }

    public function getIdpAccessTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->idpAccessTokenExpiresAt;
    }

    public function setIdpAccessTokenExpiresAt(?\DateTimeImmutable $expiry): static
    {
        $this->idpAccessTokenExpiresAt = $expiry;
        return $this;
    }

    public function getIdpRefreshTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->idpRefreshTokenExpiresAt;
    }

    public function setIdpRefreshTokenExpiresAt(?\DateTimeImmutable $expiry): static
    {
        $this->idpRefreshTokenExpiresAt = $expiry;
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
