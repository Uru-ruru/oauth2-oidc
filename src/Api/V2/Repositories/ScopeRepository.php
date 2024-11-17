<?php

namespace App\Api\V2\Repositories;

use App\Api\V2\Entities\ScopeEntity;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

/**
 * Class ScopeRepository.
 */
class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var array|\string[][]
     */
    private array $scopes = [
        'basic' => [
            'description' => 'Basic details',
        ],
        'admin' => [
            'description' => 'Admin',
        ],
        'openid' => [
            'description' => 'OIDC',
        ],
        'profile' => [
            'description' => 'Profile',
        ],
    ];

    public function getScopes(): array
    {
        $scopes = [];
        foreach ($this->scopes as $scope => $params) {
            $scopes[] = $scope;
        }

        return $scopes;
    }

    /**
     * @param mixed $identifier
     */
    public function getScopeEntityByIdentifier($identifier): false|ScopeEntity
    {
        if (false === array_key_exists($identifier, $this->scopes)) {
            return false;
        }

        $scope = new ScopeEntity();
        $scope->setIdentifier($identifier);

        return $scope;
    }

    /**
     * @param mixed      $grantType
     * @param null|mixed $userIdentifier
     *
     * @return array|ScopeEntityInterface[]
     */
    public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null): array
    {
        if (1 === (int) $userIdentifier) {
            $scope = new ScopeEntity();
            $scope->setIdentifier('admin');
            $scopes[] = $scope;
        }

        return $scopes;
    }
}
