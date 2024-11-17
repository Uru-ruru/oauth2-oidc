<?php

namespace App\Models\HL;

use App\Models\BaseD7Model;
use App\Models\User;
use Uru\BitrixModels\Exceptions\ExceptionFromBitrix;
use Uru\BitrixModels\Queries\BaseQuery;

/**
 * @property User         $user
 * @property OauthClients $client
 */
class OauthTokens extends BaseD7Model
{
    public static function tableClass(): string
    {
        return highloadblock_class('oauth_tokens');
    }

    public function user(): BaseQuery
    {
        return $this->hasOne(User::class, 'ID', 'UF_USER');
    }

    public function client(): BaseQuery
    {
        return $this->hasOne(OauthClients::class, 'ID', 'UF_CLIENT');
    }

    public function getClientId(): string
    {
        return $this['UF_CLIENT'];
    }

    public function getIdentifier(): string
    {
        return $this['UF_IDENTIFIER'];
    }

    public function getScopes(): array
    {
        return $this['UF_SCOPES'];
    }

    public function getExpiryDateTime(): ?\DateTime
    {
        return $this['UF_EXPIRY_DATETIME'] ? datetime($this['UF_EXPIRY_DATETIME']) : null;
    }

    /**
     * @param mixed $userId
     */
    public static function getTokenByUserId($userId): false|OauthTokens
    {
        return self::filter(['UF_USER' => $userId])->first();
    }

    public static function getTokenByIdentifier(string $identifier): false|OauthTokens
    {
        return self::filter(['UF_IDENTIFIER' => $identifier])->first();
    }

    /**
     * @throws ExceptionFromBitrix
     */
    public static function revokeUserToken(string $identifier): bool
    {
        $token = self::getTokenByIdentifier($identifier);
        if ($token) {
            return $token->delete();
        }

        return false;
    }

    /**
     * @param mixed $userId
     *
     * @throws ExceptionFromBitrix
     */
    public static function deleteUserToken($userId): bool
    {
        $token = self::getTokenByUserId($userId);
        if ($token) {
            return $token->delete();
        }

        return false;
    }
}
