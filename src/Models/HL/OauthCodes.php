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
class OauthCodes extends BaseD7Model
{
    public static function tableClass(): string
    {
        return highloadblock_class('oauth_codes');
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

    public function getRedirectURI(): string
    {
        return $this['UF_REDIRECT_URI'];
    }

    /**
     * @param mixed $userId
     */
    public static function getCodeByUserId($userId): false|OauthCodes
    {
        return self::filter(['UF_USER' => $userId])->first();
    }

    public static function getCodeByIdentifier(string $identifier): false|OauthCodes
    {
        return self::filter(['UF_IDENTIFIER' => $identifier])->first();
    }

    /**
     * @throws ExceptionFromBitrix
     */
    public static function revokeUserCode(string $identifier): bool
    {
        $token = self::getCodeByIdentifier($identifier);
        if ($token) {
            return $token->delete();
        }

        return false;
    }

    /**
     * @throws ExceptionFromBitrix
     */
    public static function deleteUserCode(mixed $userId): bool
    {
        $token = self::getCodeByUserId($userId);
        if ($token) {
            return $token->delete();
        }

        return false;
    }
}
