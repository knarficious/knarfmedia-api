<?php

namespace App\EventSubscriber;

use App\Entity\Publication;
use App\Service\S3Service;
use AsyncAws\Core\Exception\Exception;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber; // ← this one!
use Psr\Log\LoggerInterface;

class S3Subscriber implements EventSubscriber
{
    public function __construct(
        private S3Service $s3service,
        private string $projectDir,
        private string $bucket,
        private readonly LoggerInterface $logger,
        ) {}
        
        public function getSubscribedEvents(): array
        {
            return [
                Events::postPersist,
                Events::postRemove,
            ];
        }
        
        public function postPersist(PostPersistEventArgs $args): void
        {
            $entity = $args->getObject();
            
            if (!$entity instanceof Publication || !$entity->getFilePath()) {
                return;
            }
            
            $localPath = $this->projectDir . '/public/uploads/' . $entity->getFilePath();
            
            if (!file_exists($localPath)) {
                throw new \RuntimeException("Fichier local non trouvé : $localPath");
            }
            
            $s3Path = 'uploads/' . $entity->getFilePath();
            
            try {
                $this->s3service->upload('jaur-compartiment', $s3Path, $localPath);
            } catch (Exception $e) {
                // Better: log instead of throw (to not rollback the persist)
                 $this->logger->error($e);
                // or rethrow if you want to fail the transaction
            }
        }
        
        public function postRemove(PostRemoveEventArgs $args): void
        {
            $entity = $args->getObject();
            
            if (!$entity instanceof Publication || !$entity->getFilePath()) {
                return;
            }
            
            $key = $entity->getFilePath(); // or full 'uploads/...' if needed
            
            try {
                $this->s3service->delete($this->bucket, $key);
            } catch (Exception $e) {
                $this->logger($e);
            }
        }
}