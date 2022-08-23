<?php

namespace App\Api\V2\Repositories;

use App\Api\V2\Entities\RefreshTokenEntity;
use App\Models\HL\OauthRefreshTokens;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Uru\BitrixModels\Exceptions\ExceptionFromBitrix;

/**
 * Class RefreshTokenRepository
 * @package App\Api\V2\Repositories
 */
class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * {@inheritdoc}
     * @throws ExceptionFromBitrix
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): bool
    {
        // Some logic to persist the refresh token in a database
        if ($refreshTokenEntity->getIdentifier()) {
            OauthRefreshTokens::revokeUserToken($refreshTokenEntity->getIdentifier());
            if (OauthRefreshTokens::create([
                'UF_IDENTIFIER' => $refreshTokenEntity->getIdentifier(),
                'UF_ACCESS_TOKEN' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
                'UF_EXPIRY_DATETIME' => $refreshTokenEntity->getExpiryDateTime()->format(date_format_full()),
            ])) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     * @throws ExceptionFromBitrix
     */
    public function revokeRefreshToken($tokenId): bool
    {
        // Some logic to revoke the refresh token in a database
        if (OauthRefreshTokens::revokeUserToken($tokenId)) {
            return true;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isRefreshTokenRevoked($tokenId): bool
    {
        $token = OauthRefreshTokens::getTokenByIdentifier($tokenId);
        if ($token) {
            return false; // Access token hasn't been revoked
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewRefreshToken()
    {
        return new RefreshTokenEntity();
    }
}
