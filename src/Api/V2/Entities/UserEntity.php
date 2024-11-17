<?php

namespace App\Api\V2\Entities;

use App\Api\V2\Entities\Interfaces\UserEntityInterface;
use App\Models\User;

/**
 * Class UserEntity.
 */
class UserEntity implements UserEntityInterface
{
    /**
     * @var bool|User
     */
    private $user;

    public function __construct(?User $user = null)
    {
        $this->user = $user ?: user();
    }

    public function getUser(): bool|User
    {
        return $this->user;
    }

    /**
     * @param bool|User $user
     */
    public function setUser($user): void
    {
        $this->user = $user;
    }

    public function isUserExist(): bool
    {
        return (bool) $this->user;
    }

    /**
     * Return the user's identifier.
     */
    public function getIdentifier(): ?int
    {
        return $this->user ? $this->user->getId() : null;
    }
}
