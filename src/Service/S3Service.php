<?php

namespace App\Service;

use AsyncAws\S3\S3Client;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\Input\DeleteObjectRequest;
use AsyncAws\Core\Exception\Http\ClientException;
use Psr\Log\LoggerInterface; // pour les logs Symfony
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class S3Service
{
    private string $bucket;
    private string $baseUrl;
    
    public function __construct(
        private S3Client $s3,
        #[Autowire('%env(AWS_S3_BUCKET)%')] string $awsS3Bucket,
        #[Autowire('%env(AWS_REGION)%')] string $awsRegion,
        private ?LoggerInterface $logger = null // optionnel pour debug
        ) {
            $this->bucket = $awsS3Bucket;
            $this->baseUrl = "https://{$awsS3Bucket}.s3.{$awsRegion}.amazonaws.com";
            
            // Debug : log les infos client (retirez en prod)
            if ($this->logger) {
                $this->logger->debug('S3Service init', [
                    'bucket' => $this->bucket,
                    'region' => $awsRegion,
                    'client_region' => $this->s3->getConfiguration()['region'] ?? 'unknown',
                ]);
            }
    }
    
    public function upload(mixed $file, ?string $key = null, ?string $contentType = null): string
    {
        if ($file instanceof UploadedFile) {
            $source = $file->getPathname();
            $originalName = $file->getClientOriginalName();
            $contentType ??= $file->getMimeType() ?: 'application/octet-stream';
            $size = $file->getSize();
        } else {
            if (!is_string($file) || !file_exists($file)) {
                throw new \InvalidArgumentException('Le fichier doit être un chemin valide ou un UploadedFile');
            }
            $source = $file;
            $contentType ??= mime_content_type($source) ?: 'application/octet-stream';
            $size = filesize($source);
            $originalName = basename($source);
        }
        
        // Génération clé unique + sécurisée (SmartUniqueNamer-like)
        $extension = pathinfo($originalName, PATHINFO_EXTENSION) ?: pathinfo($source, PATHINFO_EXTENSION);
        $key = $key ?? 'uploads/' . uniqid('file_', true) . '.' . strtolower($extension);
        
        $handle = fopen($source, 'r');
        if (!$handle) {
            throw new \RuntimeException('Impossible d\'ouvrir le fichier pour lecture');
        }
        
        try {
            $request = new PutObjectRequest([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $handle,
                'ServerSideEncryption' => 'AES256',
                'ContentType' => $contentType,
                'ContentLength' => $size, // important pour streaming
                // AUCUN ACL → Bucket owner enforced + policy publique
                'Metadata' => [
                    'uploaded-by' => 'symfony-api',
                    'original-name' => $originalName,
                ],
            ]);
            
            $result = $this->s3->putObject($request)->wait();
            
            if ($this->logger) {
                $this->logger->info('Upload S3 réussi', [
                    'key' => $key,
                    'size' => $size,
                    'content_type' => $contentType,
                    'etag' => $result->getETag(),
                ]);
            }
            
            fclose($handle);
            return $key;
            
        } catch (ClientException $e) {
            fclose($handle);
            $msg = "Erreur upload S3 ({$e->getStatusCode()}): {$e->getMessage()}";
            if ($this->logger) {
                $this->logger->error($msg, ['key' => $key, 'exception' => $e->getMessage()]);
            }
            throw new \RuntimeException($msg, 0, $e);
        }
    }
    
    public function delete(string $key): void
    {
        try {
            $this->s3->deleteObject(new DeleteObjectRequest([
                'Bucket' => $this->bucket,
                'Key' => $key,
            ]))->wait();
            
            if ($this->logger) {
                $this->logger->info('Suppression S3 réussie', ['key' => $key]);
            }
            
        } catch (ClientException $e) {
            if ($e->getStatusCode() !== 404) {
                $msg = 'Erreur suppression S3 : ' . $e->getMessage();
                if ($this->logger) {
                    $this->logger->error($msg, ['key' => $key]);
                }
                throw new \RuntimeException($msg, 0, $e);
            }
            // Silencieux si 404 (déjà supprimé)
        }
    }
    
    public function getPublicUrl(string $key): string
    {
        return $this->baseUrl . '/' . ltrim($key, '/');
    }
    
    // Bonus : URL presigned (pour téléchargements temporaires sécurisés)
    public function getPresignedUrl(string $key, int $expiresInSeconds = 3600): string
    {
        return $this->s3->createPresignedRequest(
            $this->s3->getCommand('GetObject', ['Bucket' => $this->bucket, 'Key' => $key]),
            now()->addSeconds($expiresInSeconds)
            )->getUri();
    }
}