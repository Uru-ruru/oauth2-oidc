<?php

namespace App\Models\HL;

use App\Models\BaseD7Model;
use App\Models\User;

/**
 * @property User $user
 */
class OauthClients extends BaseD7Model
{
    public static function tableClass(): string
    {
        return highloadblock_class('oauth_clients');
    }

    public function getName(): string
    {
        return $this['UF_NAME'];
    }

    public function getSecret(): string
    {
        return $this['UF_SECRET'];
    }

    public function getRedirectUri(): string
    {
        return $this['UF_REDIRECT_URI'];
    }

    public function isConfidential(): bool
    {
        return (int) $this['UF_IS_CONFIDENTIAL'] > 0;
    }

    public function getCustomVariables(): array
    {
        return $this['UF_CUSTOM_VARIABLES'];
    }

    public static function getClientByName(string $name): false|OauthClients
    {
        return self::filter(['UF_NAME' => $name])->first();
    }
}
