<?php

namespace App\Api\V2\Entities\Interfaces;

/**
 * Interface ClaimEntityInterface.
 */
interface ClaimEntityInterface extends \JsonSerializable
{
    /**
     * Extra param name.
     */
    public const TYPE_ID_TOKEN = 'id_token';

    /**
     * Default value for claim type.
     */
    public const TYPE_USERINFO = 'userinfo';

    /**
     * Get the scope's identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get type of the claim.
     *
     * @return string userinfo|id_token
     */
    public function getType(): string;

    /**
     * Whether this is an essential claim.
     */
    public function getEssential(): bool;
}
