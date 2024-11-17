<?php

namespace App\Api\V2\Entities\Interfaces;

use App\Models\User;
use League\OAuth2\Server\Entities\UserEntityInterface as LeagueUserEntityInterface;

/**
 * Interface UserEntityInterface.
 */
interface UserEntityInterface extends LeagueUserEntityInterface
{
    public function getUser(): bool|User;

    public function setUser($user): void;

    public function isUserExist(): bool;
}
