<?php

namespace App\Message;

use App\Entity\Publication;

final class ExtractId3Metadata
{
    public function __construct(
        private readonly int $publicationId
    ) {
    }

    public function getPublicationId(): int
    {
        return $this->publicationId;
    }
}