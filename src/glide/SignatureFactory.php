<?php

declare(strict_types=1);

namespace think\glide;

use League\Glide\Signatures\SignatureException;
use League\Glide\Signatures\SignatureInterface;

class SignatureFactory implements SignatureInterface
{
    /**
     * Secret key used to generate signature.
     * @var string
     */
    protected $signKey;

    /**
     * Create Signature instance.
     * @param string $signKey Secret key used to generate signature.
     */
    public function __construct(string $signKey)
    {
        $this->signKey = $signKey;
    }

    /**
     * Add an HTTP signature to manipulation parameters.
     * @param string $path The resource path.
     * @param array $params The manipulation parameters.
     * @return array  The updated manipulation parameters.
     */
    public function addSignature($path, array $params)
    {
        return array_merge($params, ['sign' => $this->generateSignature($path, $params)]);
    }

    /**
     * Validate a request signature.
     * @param string $path The resource path.
     * @param array $params The manipulation params.
     * @throws SignatureException
     */
    public function validateRequest($path, array $params)
    {
        if (!isset($params['sign'])) {
            throw new SignatureException('Signature is missing.');
        }

        if ($params['sign'] !== $this->generateSignature($path, $params)) {
            throw new SignatureException('Signature is not valid.');
        }
    }

    /**
     * Generate an HTTP signature.
     * @param string $path The resource path.
     * @param array $params The manipulation parameters.
     * @return string The generated HTTP signature.
     */
    public function generateSignature(string $path,array $params)
    {
        unset($params['sign']);
        ksort($params);

        return md5($this->signKey . ':' . ltrim($path, '/') . '?' . http_build_query($params));
    }

    /**
     * Create HttpSignature instance.
     * @param string $signKey Secret key used to generate signature.
     * @return SignatureFactory The HttpSignature instance.
     */
    public static function create(string $signKey)
    {
        return new self($signKey);
    }
}
