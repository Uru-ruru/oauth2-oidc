<?php

namespace App\Api\V2\Grant;

/**
 * Trait OIDCTrait.
 */
trait OIDCTrait
{
    protected string $issuer;

    public function setIssuer($issuer): void
    {
        $this->issuer = $issuer;
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }
}
