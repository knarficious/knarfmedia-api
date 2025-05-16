<?php

namespace App\Service;

use AsyncAws\S3\S3Client;
use AsyncAws\S3\Input\PutObjectRequest;
use AsyncAws\S3\Input\DeleteObjectRequest;

class S3Service
{

    public function __construct(private S3Client $s3)
    {}
    
    public function upload(string $bucket, string $key, string $filePath): void
    {
        $this->s3->putObject(new PutObjectRequest([
            'Bucket' => $bucket,
            'Key' => 'uploads/' . $filePath,
            'Body' => fopen($filePath, 'r'),
        ]));
    }
    
    public function delete(string $bucket, string $key): void
    {
        $this->s3->deleteObject(new DeleteObjectRequest([
            'Bucket' => $bucket,
            'Key' => $key,
        ]));
    }
}

