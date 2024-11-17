<?php

namespace App\Api\V2\ResponseTypes\Interfaces;

use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface as LeagueResponseTypeInterface;

interface ResponseTypeInterface extends LeagueResponseTypeInterface
{
    public function setIdToken($idToken): void;

    public function getIdToken(): mixed;
}
