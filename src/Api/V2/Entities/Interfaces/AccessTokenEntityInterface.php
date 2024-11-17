<?php

namespace App\Api\V2\Entities\Interfaces;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface as LeagueAccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * Interface AccessTokenEntityInterface.
 */
interface AccessTokenEntityInterface extends LeagueAccessTokenEntityInterface
{
    /**
     * Return an array of claims associated with the token.
     *
     * @return ClaimEntityInterface[]
     */
    public function getClaims(): array;

    public function addClaim($claim): void;

    /**
     * Return an array of scopes associated with the token.
     *
     * @return ScopeEntityInterface[]
     */
    public function getScopes(): array;
}
