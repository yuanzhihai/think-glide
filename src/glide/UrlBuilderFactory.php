<?php
declare(strict_types=1);

namespace think\glide;

use League\Glide\Signatures\SignatureInterface;

class UrlBuilderFactory
{
    /**
     * The base URL.
     * @var string
     */
    protected $baseUrl;

    /**
     * Whether the base URL is a relative domain.
     * @var bool
     */
    protected $isRelativeDomain = false;

    /**
     * The HTTP signature used to sign URLs.
     * @var SignatureInterface
     */
    protected $signature;

    /**
     * Create UrlBuilder instance.
     * @param string $baseUrl   The base URL.
     * @param SignatureInterface|null $signature The HTTP signature used to sign URLs.
     */
    public function __construct(string $baseUrl = '',SignatureInterface $signature = null)
    {
        $this->setBaseUrl($baseUrl);
        $this->setSignature($signature);
    }

    /**
     * Set the base URL.
     * @param string $baseUrl The base URL.
     */
    public function setBaseUrl(string $baseUrl)
    {
        if (substr($baseUrl, 0, 2) === '//') {
            $baseUrl = 'http:'.$baseUrl;
            $this->isRelativeDomain = true;
        }

        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Set the HTTP signature.
     * @param SignatureInterface|null $signature The HTTP signature used to sign URLs.
     */
    public function setSignature(SignatureInterface $signature = null)
    {
        $this->signature = $signature;
    }

    /**
     * Get the URL.
     * @param string $path   The resource path.
     * @param  array  $params The manipulation parameters.
     * @return string The URL.
     */
    public function getUrl(string $path,array $params = [])
    {
        $parts = parse_url($this->baseUrl.'/'.trim($path, '/'));

        if ($parts === false) {
            throw new \InvalidArgumentException('Not a valid path.');
        }

        $parts['path'] = '/'.trim($parts['path'], '/');

        if ($this->signature) {
            $params = $this->signature->addSignature($parts['path'], $params);
        }

        return $this->buildUrl($parts, $params);
    }

    /**
     * Build the URL.
     * @param array $parts  The URL parts.
     * @param array $params The manipulation parameters.
     * @return string The built URL.
     */
    protected function buildUrl(array $parts,array $params)
    {
        $url = '';

        if (isset($parts['host'])) {
            if ($this->isRelativeDomain) {
                $url .= '//'.$parts['host'];
            } else {
                $url .= $parts['scheme'].'://'.$parts['host'];
            }

            if (isset($parts['port'])) {
                $url .= ':'.$parts['port'];
            }
        }

        $url .= $parts['path'];

        if (count($params)) {
            $url .= '?'.http_build_query($params);
        }

        return $url;
    }

    /**
     * Create UrlBuilder instance.
     * @param string $baseUrl URL prefixed to generated URL.
     * @param string|null $signKey Secret key used to secure URLs.
     * @return UrlBuilderFactory  The UrlBuilder instance.
     */
    public static function create(string $baseUrl,string $signKey = null)
    {
        $httpSignature = null;

        if ($signKey) {
            $httpSignature = SignatureFactory::create($signKey);
        }

        return new self($baseUrl, $httpSignature);
    }
}
