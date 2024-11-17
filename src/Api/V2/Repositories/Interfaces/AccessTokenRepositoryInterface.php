<?php

namespace App\Api\V2\Repositories\Interfaces;

use App\Api\V2\Entities\Interfaces\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface as EntitiesAccessTokenEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface as LeagueAccessTokenRepositoryInterface;

interface AccessTokenRepositoryInterface extends LeagueAccessTokenRepositoryInterface
{
    /**
     * @param array $claims ClaimEntityInterface[]
     */
    public function storeClaims(EntitiesAccessTokenEntityInterface $token, array $claims);

    /**
     * Retrieve an access token.
     */
    public function getAccessToken(string $tokenId): AccessTokenEntityInterface;
}
