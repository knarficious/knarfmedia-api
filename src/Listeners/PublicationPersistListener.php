<?php 
// src/EventListener/PublicationPersistListener.php

namespace App\Listeners;

use App\Entity\Publication;
use App\Message\ExtractId3Metadata;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]   // optional: if file changes on update
final class PublicationPersistListener
{
    public function __construct(
        private readonly MessageBusInterface $bus
    ) {
    }

    public function postPersist($eventArgs): void
    {
        $this->handle($eventArgs->getObject());
    }

    public function postUpdate($eventArgs): void
    {
        $this->handle($eventArgs->getObject());
    }

    private function handle(object $entity): void
    {
        if (!$entity instanceof Publication) {
            return;
        }

        // Optional: check if file was actually uploaded/changed
        // You can inspect UnitOfWork change set if needed
        // For simplicity: always check extension if filePath exists
        $files = $entity->getFiles();  // adjust getter name
        
        foreach ($files as $file) {
            $fileName = $file->getFileName();
            if (!$fileName || !str_ends_with(strtolower($fileName), '.mp3')) {
                return;
            }
        }

        $id = $entity->getId();
        if ($id === null) {
            // Should never happen here, but safety
            return;
        }

        $this->bus->dispatch(new ExtractId3Metadata($id));
    }
}