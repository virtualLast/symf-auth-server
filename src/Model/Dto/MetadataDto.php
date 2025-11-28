<?php

namespace App\Model\Dto;

class MetadataDto
{
    private string $certificate;

    public function setCertificate(string $certificate): MetadataDto
    {
        $this->certificate = $certificate;
        return $this;
    }

    public function getCertificate(): string
    {
        return $this->certificate;
    }
}
