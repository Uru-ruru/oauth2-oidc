<?php

namespace App\Models\HL;

use App\Models\BaseD7Model;
use Uru\BitrixModels\Exceptions\ExceptionFromBitrix;

class OauthRefreshTokens extends BaseD7Model
{
    public static function tableClass(): string
    {
        return highloadblock_class('oauth_refresh_tokens');
    }

    public function getIdentifier(): string
    {
        return $this['UF_IDENTIFIER'];
    }

    public function getAccessToken(): string
    {
        return $this['UF_ACCESS_TOKEN'];
    }

    public function getExpiryDateTime(): ?\DateTime
    {
        return $this['UF_EXPIRY_DATETIME'] ? datetime($this['UF_EXPIRY_DATETIME']) : null;
    }

    public static function getTokenByIdentifier(string $identifier): false|OauthRefreshTokens
    {
        return self::filter(['UF_IDENTIFIER' => $identifier])->first();
    }

    public static function getTokenByAccessIdentifier(string $identifier): false|OauthRefreshTokens
    {
        return self::filter(['UF_ACCESS_TOKEN' => $identifier])->first();
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
}
