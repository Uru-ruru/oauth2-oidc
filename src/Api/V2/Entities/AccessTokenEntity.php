<?php

namespace App\Api\V2\Entities;

use App\Api\V2\Entities\Interfaces\AccessTokenEntityInterface;
use App\Models\User;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * Class AccessTokenEntity.
 */
class AccessTokenEntity implements AccessTokenEntityInterface
{
    use AccessTokenTrait;
    use TokenEntityTrait;
    use EntityTrait;

    private string $email;

    private string $usertype;

    private array $claims = [];

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param mixed $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    public function getUsertype(): string
    {
        return $this->usertype;
    }

    public function setUsertype(User $user): void
    {
        $usertype = 'Unknown';

        if ($user->isPartner() || $user->isDeveloper()) {
            $usertype = 'Client';
        }
        if ($user->isManager()) {
            $usertype = 'Employee';
        }
        if ($user->isSupportEngineer()) {
            $usertype = 'Employee';
        }
        if ($user->isAcademyTrainer()) {
            $usertype = 'Employee';
        }
        if ($user->isAdmin()) {
            $usertype = 'Employee';
        }

        $this->usertype = $usertype;
    }

    public function setUserIdentifier($identifier): void
    {
        $this->userIdentifier = $identifier;
        $user = User::getById($identifier);
        if ($user) {
            $this->setEmail($user->getEmail());
            $this->setUsertype($user);
        }
    }

    /**
     * Return an array of scopes associated with the token.
     */
    public function getScopes(): array
    {
        return array_values($this->scopes);
    }

    public function addClaim($claim): void
    {
        $this->claims[] = $claim;
    }

    /**
     * @return array|Interfaces\ClaimEntityInterface[]
     */
    public function getClaims(): array
    {
        return $this->claims;
    }

    /**
     * Generate a JWT from the access token.
     */
    private function convertToJWT(): Token
    {
        $this->initJwtConfiguration();

        return $this->jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new \DateTimeImmutable())
            ->canOnlyBeUsedAfter(new \DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo((string) $this->getUserIdentifier())
            ->withClaim('scopes', $this->getScopes())
            ->withClaim('email', $this->getEmail())
            ->withClaim('usertype', $this->getUsertype())
            ->withClaim('claims', $this->getClaims())
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey())
        ;
    }
}
