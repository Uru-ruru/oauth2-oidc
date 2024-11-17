<?php

namespace App\Api\V2\Repositories\Interfaces;

use App\Api\V2\Entities\Interfaces\ClaimEntityInterface;
use App\Api\V2\Entities\Interfaces\UserEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface as LeagueUserRepositoryInterface;

interface UserRepositoryInterface extends LeagueUserRepositoryInterface
{
    /**
     * Return the claims related to a scope.
     *
     * @return ClaimEntityInterface[]
     */
    public function getClaims(ClaimRepositoryInterface $claimRepository, ScopeEntityInterface $scope): array;

    /**
     * Returns an associative array with attribute (claim) keys and values.
     *
     * @param mixed $claims
     * @param mixed $scopes
     */
    public function getAttributes(UserEntityInterface $userEntity, $claims, $scopes);

    /**
     * Returns an associative array with attribute (claim) keys and values. For use by userinfo endpoint.
     *
     * @param mixed $claims
     * @param mixed $scopes
     */
    public function getUserInfoAttributes(UserEntityInterface $userEntity, $claims, $scopes);

    /**
     * Return User.
     *
     * @param mixed $identifier
     */
    public function getUserByIdentifier($identifier): ?UserEntityInterface;
}
