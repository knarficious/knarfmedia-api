<?php

namespace App\Encoder;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class MultipartDecoder implements DecoderInterface
{
    public const FORMAT = 'multipart';
    
    public function __construct(private RequestStack $requestStack) {}
    
    public function decode(string $data, string $format, array $context = []): ?array
    {
        $request = $this->requestStack->getCurrentRequest();
        
        if (!$request) {
            return null;
        }
        
        $decoded = [];
        
        foreach ($request->request->all() as $key => $value) {
            // Handle scalar string values (most fields)
            if (is_string($value)) {
                // Try JSON decode only if it looks like JSON (starts with { or [)
                if (str_starts_with(trim($value), '{') || str_starts_with(trim($value), '[')) {
                    $json = json_decode($value, true);
                    $decoded[$key] = (json_last_error() === JSON_ERROR_NONE) ? $json : $value;
                } else {
                    $decoded[$key] = $value;
                }
            }
            // Handle already-parsed arrays (e.g. tags[] → array of strings)
            elseif (is_array($value)) {
                $decoded[$key] = $value; // keep as-is (IRIs or values)
            }
            // Fallback for other weird cases
            else {
                $decoded[$key] = $value;
            }
        }
        
        // Add files (UploadedFile instances)
        foreach ($request->files->all() as $key => $file) {
            $decoded[$key] = $file;
        }
        
        return $decoded;
    }
    
    public function supportsDecoding(string $format): bool
    {
        return self::FORMAT === $format;
    }
}