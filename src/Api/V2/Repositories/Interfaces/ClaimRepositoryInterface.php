<?php

namespace App\Api\V2\Repositories\Interfaces;

use App\Api\V2\Entities\Interfaces\ClaimEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\RepositoryInterface;

/**
 * Claim interface.
 */
interface ClaimRepositoryInterface extends RepositoryInterface
{
    /**
     * Return information about a claim.
     *
     * @param string $identifier The claim identifier
     * @param mixed  $type
     * @param mixed  $essential
     */
    public function getClaimEntityByIdentifier(string $identifier, $type, $essential): ?ClaimEntityInterface;

    /**
     * @return ClaimEntityInterface[]
     */
    public function getClaimsByScope(ScopeEntityInterface $scope): iterable;

    public function claimsRequestToEntities(?array $json = null);
}
