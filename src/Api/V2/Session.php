<?php

namespace App\Api\V2;

/**
 * Class Session.
 */
class Session
{
    public function getAuthTime(): \DateTime
    {
        return new \DateTime();
    }
}
