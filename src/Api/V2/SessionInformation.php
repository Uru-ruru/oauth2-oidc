<?php

namespace App\Api\V2;

/**
 * Class SessionInformation.
 */
class SessionInformation
{
    public $acr;

    public $amr;

    public $azp;

    /**
     * @throws \JsonException
     */
    public function __toString(): string
    {
        return $this->toJSON() ?: '';
    }

    /**
     * @param mixed $json
     *
     * @throws \JsonException
     */
    public static function fromJSON($json): SessionInformation
    {
        $json = json_decode($json, false, 512, JSON_THROW_ON_ERROR);

        $result = new self();

        $result->setAzp($json->azp);
        $result->setAcr($json->acr);
        $result->setAzp($json->azp);

        return $result;
    }

    /**
     * @throws \JsonException
     */
    public function toJSON(): false|string
    {
        return json_encode(['acr' => $this->acr, 'amr' => $this->amr, 'azp' => $this->azp], JSON_THROW_ON_ERROR);
    }

    /**
     * Get the value of acr.
     */
    public function getAcr()
    {
        return $this->acr;
    }

    /**
     * Set the value of acr.
     *
     * @param mixed $acr
     */
    public function setAcr($acr): SessionInformation
    {
        $this->acr = $acr;

        return $this;
    }

    /**
     * Get the value of amr.
     */
    public function getAmr()
    {
        return $this->amr;
    }

    /**
     * Set the value of amr.
     *
     * @param mixed $amr
     */
    public function setAmr($amr): SessionInformation
    {
        $this->amr = $amr;

        return $this;
    }

    /**
     * Get the value of azp.
     */
    public function getAzp()
    {
        return $this->azp;
    }

    /**
     * Set the value of azp.
     *
     * @param mixed $azp
     */
    public function setAzp($azp): SessionInformation
    {
        $this->azp = $azp;

        return $this;
    }
}
