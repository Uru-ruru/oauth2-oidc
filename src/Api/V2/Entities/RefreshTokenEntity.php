<?php

namespace App\Api\V2\Entities;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\RefreshTokenTrait;

/**
 * Class RefreshTokenEntity.
 */
class RefreshTokenEntity implements RefreshTokenEntityInterface
{
    use RefreshTokenTrait;
    use EntityTrait;
}
