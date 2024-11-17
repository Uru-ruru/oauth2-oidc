<?php

namespace App\Api\V2\Entities;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

/**
 * Class ScopeEntity.
 */
class ScopeEntity implements ScopeEntityInterface
{
    use EntityTrait;
    use ScopeTrait;

    public function __construct($identifier = null)
    {
        if ($identifier) {
            $this->setIdentifier($identifier);
        }
    }
}
