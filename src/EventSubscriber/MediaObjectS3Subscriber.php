<?php

namespace App\EventSubscriber;

use App\Entity\MediaObject;
use App\Service\S3Service;
use AsyncAws\Core\Exception\Exception;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MediaObjectS3Subscriber implements EventSubscriber
{
    public function __construct(
        private readonly S3Service $s3Service,
        private readonly LoggerInterface $logger,
        private readonly string $bucketName,   // ex: 'jaur-compartiment'
    ) {}

    /**
     * Cette méthode n'est plus nécessaire quand on utilise #[AsDoctrineListener]
     * Mais on la garde pour compatibilité ou clarté
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postRemove,
        ];
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof MediaObject) {
            return;
        }

        $key = $entity->getFileName() ?? $entity->getContentUrl();

        if (empty($key)) {
            $this->logger->warning('MediaObject supprimé sans chemin S3', ['id' => $entity->getId()]);
            return;
        }

        // Nettoyage du chemin si c'est une URL complète
        if (str_starts_with($key, 'http')) {
            $key = parse_url($key, PHP_URL_PATH);
            $key = ltrim($key, '/');
        }

        try {
            $this->s3Service->delete($this->bucketName, $key);
            $this->logger->info("Fichier supprimé avec succès de S3", [
                'mediaObjectId' => $entity->getId(),
                'key' => $key
            ]);
        } catch (Exception $e) {
            $this->logger->error("Échec suppression S3 pour MediaObject", [
                'mediaObjectId' => $entity->getId(),
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            // On ne throw pas : on ne veut pas faire échouer la suppression en base de données
        }
    }
}