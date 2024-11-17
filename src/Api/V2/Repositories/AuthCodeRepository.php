<?php

namespace App\Api\V2\Repositories;

use App\Api\V2\Entities\AuthCodeEntity;
use App\Models\HL\OauthClients;
use App\Models\HL\OauthCodes;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Uru\BitrixModels\Exceptions\ExceptionFromBitrix;

/**
 * Class AuthCodeRepository.
 */
class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * @throws ExceptionFromBitrix
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): bool
    {
        // Some logic to persist the auth code to a database
        if ($authCodeEntity->getUserIdentifier() && $authCodeEntity->getIdentifier()) {
            OauthCodes::deleteUserCode($authCodeEntity->getUserIdentifier());
            $client = OauthClients::getClientByName($authCodeEntity->getClient()->getName());
            if (OauthCodes::create([
                'UF_USER' => $authCodeEntity->getUserIdentifier(),
                'UF_IDENTIFIER' => $authCodeEntity->getIdentifier(),
                'UF_SCOPES' => array_map(static function ($val) {
                    return $val->getIdentifier();
                }, $authCodeEntity->getScopes()),
                'UF_EXPIRY_DATETIME' => $authCodeEntity->getExpiryDateTime()->format(date_format_full()),
                'UF_CLIENT' => $client ? $client->getId() : '',
                'UF_REDIRECT_URI' => $authCodeEntity->getRedirectUri(),
            ])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $codeId
     *
     * @throws ExceptionFromBitrix
     */
    public function revokeAuthCode($codeId): bool
    {
        // Some logic to revoke the auth code in a database
        if (OauthCodes::revokeUserCode($codeId)) {
            return true;
        }

        return false;
    }

    public function isAuthCodeRevoked($codeId): bool
    {
        $code = OauthCodes::getCodeByIdentifier($codeId);
        if ($code) {
            return false; // Access code hasn't been revoked
        }

        return true;
    }

    public function getNewAuthCode(): AuthCodeEntity
    {
        $authCode = new AuthCodeEntity();
        if (user()) {
            $authCode->setUserIdentifier(user()->getId());
        }

        return $authCode;
    }
}
