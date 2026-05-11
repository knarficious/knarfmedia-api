<?php

namespace App\Serializer;

use App\Entity\MediaObject;
use App\Service\S3Service;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

final class MediaObjectNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    
    private const ALREADY_CALLED = 'MEDIA_OBJECT_NORMALIZER_ALREADY_CALLED';
    
    public function __construct(
        private S3Service $s3Service,
        private readonly StorageInterface $storage
        ) {}
        
        public function normalize(mixed $object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
        {
            if (isset($context[self::ALREADY_CALLED])) {
                // Version minimale pour éviter les boucles infinies
                return [
                    '@id' => $object->getId() ? '/media_objects/' . $object->getId() : null,
                    'contentUrl' => $object->contentUrl,
                    'fileName' => $object->getFileName() ?? basename($object->contentUrl ?? ''),
                    'originalFileName' => $object->getFileOriginalName() ?? null,
                  //  'mimeType' => $object->getMimeType() ?? null,
                ];
            }
            
            $context[self::ALREADY_CALLED] = true;
            
            /** @var MediaObject $object */
            
            // Génère l'URL pré-signée à partir du fileName (clé S3)
            if (!empty($object->getFileName())) {
                try {
                    $object->setContentUrl(
                        $this->s3Service->getPresignedUrl($object->getFileName(), 3600)
                        );
                } catch (\Exception $e) {
                    $this->logger?->warning('Impossible de générer une URL pré-signée', [
                        'id'       => $object->getId(),
                        'fileName' => $object->getFileName(),
                        'error'    => $e->getMessage(),
                    ]);
                    $object->setContentUrl(null);
                }
            }
            
            $normalized = $this->normalizer->normalize($object, $format, $context);
            
            return $normalized;
        }
        
        public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
        {
            if (isset($context[self::ALREADY_CALLED])) {
                return false;
            }
            
            return $data instanceof MediaObject;
        }
        
        public function getSupportedTypes(?string $format): array
        {
            return [
                MediaObject::class => true,
            ];
        }
}