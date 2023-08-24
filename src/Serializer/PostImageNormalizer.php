<?php
// api/src/Serializer/MediaObjectNormalizer.php

namespace App\Serializer;

use App\Entity\Publication;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Vich\UploaderBundle\Storage\StorageInterface;

final class PostImageNormalizer implements ContextAwareNormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;
    
    private const ALREADY_CALLED = 'POST_IMAGE_NORMALIZER_ALREADY_CALLED';
    
    public function __construct(private StorageInterface $storage)
    {
    }
    
    public function supportsNormalization($data, string $format = null, array $context = [] ): bool
    {
        return !isset($context[self::ALREADY_CALLED]) && $data instanceof Publication;
    }
    
    /**
     * @param Publication $object
     */
    public function normalize($object, string $format = null, array $context  = [])
    {
        $object->setFilePath($this->storage->resolveUri($object, 'file'));
        $context[self::ALREADY_CALLED] = true;
        
        return $this->normalizer->normalize($object, $format, $context);
    }

}