<?php

namespace App\Api\V2\Entities;

use App\Api\V2\Entities\Interfaces\ClaimEntityInterface;

/**
 * Class ClaimEntity.
 */
class ClaimEntity implements ClaimEntityInterface
{
    private string $type;

    /**
     * @var bool|mixed
     */
    private bool $essential;

    private string $identifier;

    public function __construct($identifier, $type, bool $essential = true)
    {
        $this->identifier = $identifier;
        $this->type = $type;
        $this->essential = $essential;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEssential(): bool
    {
        return $this->essential;
    }

    public function jsonSerialize(): array
    {
        return [
            'identifier' => $this->getIdentifier(),
            'type' => $this->getType(),
            'essential' => $this->getEssential(),
        ];
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
