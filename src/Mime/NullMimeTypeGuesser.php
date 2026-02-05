<?php

namespace App\Mime;

use Symfony\Component\Mime\MimeTypeGuesserInterface;

class NullMimeTypeGuesser implements MimeTypeGuesserInterface
{
    public function guessMimeType(string $path): ?string
    {
        // Si le fichier n'existe pas localement (S3 case), retourne null ou fallback
        if (!file_exists($path) || !is_readable($path)) {
            return null; // ou 'application/octet-stream'
        }

        // Sinon, fallback au guesser par défaut
        return (new \Symfony\Component\Mime\FileinfoMimeTypeGuesser())->guessMimeType($path);
    }
    public function isGuesserSupported(): bool
    {
        return true;
    }

}