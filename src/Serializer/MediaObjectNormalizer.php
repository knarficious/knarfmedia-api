<?php

namespace App\Serializer;

use App\Entity\MediaObject;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Storage\StorageInterface;

final class MediaObjectNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    
    private const ALREADY_CALLED = 'MEDIA_OBJECT_NORMALIZER_ALREADY_CALLED';
    
    public function __construct(
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
            
            if ($object instanceof MediaObject) {
                // Génère l'URL S3 si elle n'existe pas encore
                if (empty($object->contentUrl)) {
                    try {
                        $object->contentUrl = $this->storage->resolveUri($object, 'file');
                    } catch (\Exception $e) {
                        $this->logger?->warning('Impossible de générer contentUrl pour MediaObject', ['id' => $object->getId()]);
                        $object->contentUrl = null;
                    }
                }
                
                // On NE supprime PLUS la relation publication (trop risqué)
                // On laisse le normalizer principal gérer la récursion avec le flag ALREADY_CALLED
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