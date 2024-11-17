<?php

namespace App\Api\V2\Repositories;

use App\Api\V2\Entities\AccessTokenEntity;
use App\Api\V2\Entities\ClientEntity;
use App\Api\V2\Entities\ScopeEntity;
use App\Api\V2\Repositories\Interfaces\AccessTokenRepositoryInterface;
use App\Models\HL\OauthClients;
use App\Models\HL\OauthTokens;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Uru\BitrixModels\Exceptions\ExceptionFromBitrix;

/**
 * Class AccessTokenRepository.
 */
class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * @throws ExceptionFromBitrix
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): bool
    {
        if ($accessTokenEntity->getUserIdentifier() && $accessTokenEntity->getIdentifier()) {
            OauthTokens::deleteUserToken($accessTokenEntity->getUserIdentifier());
            $client = OauthClients::getClientByName($accessTokenEntity->getClient()->getName());
            if (OauthTokens::create([
                'UF_USER' => $accessTokenEntity->getUserIdentifier(),
                'UF_IDENTIFIER' => $accessTokenEntity->getIdentifier(),
                'UF_SCOPES' => array_map(static function ($val) {
                    return $val->getIdentifier();
                }, $accessTokenEntity->getScopes()),
                'UF_EXPIRY_DATETIME' => $accessTokenEntity->getExpiryDateTime()->format(date_format_full()),
                'UF_CLIENT' => $client ? $client->getId() : '',
            ])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $tokenId
     *
     * @throws ExceptionFromBitrix
     */
    public function revokeAccessToken($tokenId): bool
    {
        if (OauthTokens::revokeUserToken($tokenId)) {
            return true;
        }

        return false;
    }

    public function isAccessTokenRevoked($tokenId): bool
    {
        $token = OauthTokens::getTokenByIdentifier($tokenId);
        if ($token) {
            return false; // Access token hasn't been revoked
        }

        return true;
    }

    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null): AccessTokenEntity
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        $accessToken->setUserIdentifier($userIdentifier);

        return $accessToken;
    }

    public function storeClaims(AccessTokenEntityInterface $token, array $claims): void
    {
        foreach ($claims as $claim) {
            // @var \App\Api\V2\Entities\Interfaces\AccessTokenEntityInterface $token
            $token->addClaim($claim);
        }
    }

    /**
     * @throws \Exception
     */
    public function getAccessToken(string $tokenId): \App\Api\V2\Entities\Interfaces\AccessTokenEntityInterface
    {
        $token = OauthTokens::getTokenByIdentifier($tokenId);
        if ($token) {
            $accessToken = new AccessTokenEntity();
            $accessToken->setUserIdentifier($token->user->getId());
            $accessToken->setIdentifier($token->getIdentifier());
            $accessToken->setClient(new ClientEntity($token->getClientId()));
            $accessToken->setExpiryDateTime(new \DateTimeImmutable($token->getExpiryDateTime() ? $token->getExpiryDateTime()->format(date_format_full()) : null));
            foreach ($token->getScopes() as $scope) {
                $accessToken->addScope(new ScopeEntity($scope));
            }

            return $accessToken;
        }

        return new AccessTokenEntity();
    }
}
