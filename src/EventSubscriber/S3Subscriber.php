<?php 

namespace App\EventSubscriber;

use App\Entity\Publication;
use App\Service\S3Service;
use AsyncAws\Core\Exception\Exception;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\Events;

class S3Subscriber implements EventSubscriberInterface
{
    public function __construct(
        private S3Service $s3service,
        private string $projectDir,
        private string $bucket
    ) {}

    static public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::postRemove,
            
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
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
            // Log erreur, ou réagir en conséquence
             throw $e; // ou ne rien faire pour ne pas casser la requête
        }
    }
    
    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity =$args->getObject();
        
        if (!$entity instanceof Publication || !$entity->getFilePath()) {
            return;
        }
        
        $key = $entity->getFilePath();
        
        if (!$key) {
            return;
        }
        
        try {
            $this->s3service->delete($this->bucket, $key);
        } catch (Exception $e) {
            throw $e;
        }
        
    }
}
