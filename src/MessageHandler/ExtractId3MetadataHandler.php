<?php
// src/MessageHandler/ExtractId3MetadataHandler.php

namespace App\MessageHandler;

use App\Entity\Publication;
use App\Message\ExtractId3Metadata;
use Doctrine\ORM\EntityManagerInterface;
use getID3;
use getid3_lib;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use AsyncAws\S3\S3Client;
use AsyncAws\S3\Result\GetObjectOutput;

#[AsMessageHandler]
final class ExtractId3MetadataHandler
{
    private string $bucket;
    private string $baseUrl;
    
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly S3Client $s3Client,
        #[Autowire('%env(AWS_S3_BUCKET)%')] string $awsS3Bucket,
        #[Autowire('%env(AWS_REGION)%')] string $awsRegion,
        private readonly LoggerInterface $logger,
        ) {            
            $this->bucket = $awsS3Bucket;
            $this->baseUrl = "https://{$awsS3Bucket}.s3.{$awsRegion}.amazonaws.com";
            
            // Debug : log les infos client (retirez en prod)
            if ($this->logger) {
                $this->logger->debug('S3Service init', [
                    'bucket' => $this->bucket,
                    'region' => $awsRegion,
                  //  'client_region' => $this->s3Client->getConfiguration()['region'] ?? 'unknown',
                ]);
            }
    }
    
    public function __invoke(ExtractId3Metadata $message): void
    {
        $publication = $this->em->find(Publication::class, $message->getPublicationId());
        
        if (!$publication) {
            $this->logger->warning('Publication not found for ID3 extraction', ['id' => $message->getPublicationId()]);
            return;
        }
        
        // Get the file path (relative key in S3)
        $fileKey = 'uploads/' . $publication->getFilePath(); // adjust to your Vich getter, e.g. getFile() or mapping logic
        
        if (!$fileKey) {
            return;
        }
        
        $tempFile = null;
        try {
            $result = $this->s3Client->getObject([
                'Bucket' => $this->bucket,
                'Key'    => $fileKey,
            ]);
            
            $body = $result->getBody();
            
            $tempFile = tempnam(sys_get_temp_dir(), 'id3_');
            $written = file_put_contents($tempFile, $body);
            
            if ($written === false) {
                throw new \RuntimeException('Failed to write S3 response to temp file');
            }
            
            $this->logger->info('MP3 downloaded from S3', [
                'key' => $fileKey,
                'size_bytes' => $written,
            ]);
            
            // Proceed with getID3 analysis...
            $getID3 = new getID3();
            $info = $getID3->analyze($tempFile);
            getid3_lib::CopyTagsToComments($info);
            
            $this->logger->debug('getID3 raw info keys', ['keys' => array_keys($info)]);
            if (isset($info['tags'])) {
                $this->logger->debug('ID3 tags present', ['tags' => array_keys($info['tags'])]);
            }
            if (isset($info['comments']['picture'])) {
                $this->logger->info('Embedded picture found in comments.picture', [
                    'mime' => $info['comments']['picture'][0]['image_mime'] ?? 'unknown',
                    'size' => strlen($info['comments']['picture'][0]['data'] ?? '') . ' bytes'
                ]);
            } else {
                $this->logger->warning('No picture in comments.picture');
            }
            
            // Check alternative location (raw ID3v2 APIC frame)
            $coverData = null;
            $coverMime = 'image/jpeg';
            $coverExt  = 'jpg';
            
            if (!empty($info['comments']['picture'][0]['data'])) {
                $pic = $info['comments']['picture'][0];
                $coverData = $pic['data'];
                $coverMime = $pic['image_mime'] ?? 'image/jpeg';
            } elseif (!empty($info['id3v2']['APIC'][0]['data'])) {  // fallback raw APIC
                $pic = $info['id3v2']['APIC'][0];
                $coverData = $pic['data'];
                $coverMime = $pic['mime'] ?? 'image/jpeg';
                $this->logger->info('Picture found in raw id3v2 APIC');
            }
            
            if ($coverData) {
                if (stripos($coverMime, 'png') !== false) $coverExt = 'png';
                if (stripos($coverMime, 'gif') !== false) $coverExt = 'gif';
                
                $coverKey = sprintf('uploads/covers/%d.%s', $publication->getId(), $coverExt);
                
                $this->s3Client->putObject([
                    'Bucket'      => $this->bucket,
                    'Key'         => $coverKey,
                    'Body'        => $coverData,
                    'ContentType' => $coverMime,
                ]);
                
                $publication->setCoverPath($coverKey);
                $this->logger->info('Cover uploaded and path set', ['coverKey' => $coverKey]);
                
                $this->em->flush();
            } else {
                $this->logger->warning('No embedded cover art found in MP3');
            }
            
            // Optional: extract basic tags too
            $tags = $info['tags']['id3v2'] ?? $info['tags']['id3v1'] ?? [];
            if (!empty($tags['title'][0])) {
                // $publication->setTitle($tags['title'][0]);  // if you want to override
            }
            if (!empty($tags['artist'][0])) {
                // $publication->setArtist($tags['artist'][0]);
            }
            
        } catch (\Throwable $e) {
            $this->logger->error('ID3 extraction failed during S3 download or parsing', [
                'publication_id' => $publication->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Optional: $this->em->flush(); if you want to mark failure
        } finally {
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }
}
