<?php

namespace App\Entity;

use App\Model\Enum\ProviderEnum;
use App\Repository\UserRepository;
use App\Trait\CreatedUpdatedTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_users')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_PROVIDER_SUB', fields: ['provider', 'tokenSub'])]
#[ORM\Index(name: 'IDX_USER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface
{
    use CreatedUpdatedTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON, options: ['default' => '["ROLE_USER"]'])]
    private array $roles = ['ROLE_USER'];

    /**
     * @var list<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $accessLevels = null;

    #[ORM\Column(length: 180, nullable: false)]
    private string $tokenSub; // The Keycloak "sub"

    #[ORM\Column(type: "string", enumType: ProviderEnum::class)]
    private ProviderEnum $provider;

    /**
     * @var Collection<int, Token>
     */
    #[ORM\OneToMany(targetEntity: Token::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $tokens;

    public function __construct()
    {
        $this->tokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return sprintf('%s_%s', $this->provider->value, $this->tokenSub);
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        
        // Ensure ROLE_USER is always present (security measure)
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getTokenSub(): ?string
    {
        return $this->tokenSub;
    }

    public function setTokenSub(string $tokenSub): static
    {
        $this->tokenSub = $tokenSub;
        return $this;
    }

    public function getProvider(): ProviderEnum
    {
        return $this->provider;
    }

    public function setProvider(ProviderEnum $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function getAccessLevels(): ?array
    {
        return $this->accessLevels;
    }

    public function setAccessLevels(?array $accessLevels): static
    {
        $this->accessLevels = $accessLevels;
        return $this;
    }

    public function __serialize(): array
    {
        $data = (array) $this;
        unset($data['password']); // users do not have passwords here
        return $data;
    }

    /**
     * @return Collection<int, Token>
     */
    public function getTokens(): Collection
    {
        return $this->tokens;
    }

    public function addToken(Token $token): static
    {
        if (!$this->tokens->contains($token)) {
            $this->tokens->add($token);
            $token->setUser($this);
        }
        return $this;
    }

    public function removeToken(Token $token): static
    {
        if ($this->tokens->removeElement($token)) {
            if ($token->getUser() === $this) {
                $token->setUser(null);
            }
        }
        return $this;
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }
}
