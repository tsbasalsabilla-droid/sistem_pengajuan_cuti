<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Exceptions\InvalidArgumentException;
use Config\App;
use Config\ContentSecurityPolicy as ContentSecurityPolicyConfig;


class ContentSecurityPolicy
{
    private const DIRECTIVES_ALLOWING_SOURCE_LISTS = [
        'base-uri'        => 'baseURI',
        'child-src'       => 'childSrc',
        'connect-src'     => 'connectSrc',
        'default-src'     => 'defaultSrc',
        'font-src'        => 'fontSrc',
        'form-action'     => 'formAction',
        'frame-ancestors' => 'frameAncestors',
        'frame-src'       => 'frameSrc',
        'img-src'         => 'imageSrc',
        'media-src'       => 'mediaSrc',
        'object-src'      => 'objectSrc',
        'plugin-types'    => 'pluginTypes',
        'script-src'      => 'scriptSrc',
        'style-src'       => 'styleSrc',
        'sandbox'         => 'sandbox',
        'manifest-src'    => 'manifestSrc',
        'script-src-elem' => 'scriptSrcElem',
        'script-src-attr' => 'scriptSrcAttr',
        'style-src-elem'  => 'styleSrcElem',
        'style-src-attr'  => 'styleSrcAttr',
        'worker-src'      => 'workerSrc',
    ];

    
    protected array $directives = [
        ...self::DIRECTIVES_ALLOWING_SOURCE_LISTS,
        'report-uri' => 'reportURI',
        'report-to'  => 'reportTo',
    ];

    
    protected $baseURI = [];

    
    protected $childSrc = [];

    
    protected $connectSrc = [];

    
    protected $defaultSrc = [];

    
    protected $fontSrc = [];

    
    protected $formAction = [];

    
    protected $frameAncestors = [];

    
    protected $frameSrc = [];

    
    protected $imageSrc = [];

    
    protected $mediaSrc = [];

    
    protected $objectSrc = [];

    
    protected $pluginTypes = [];

    
    protected $scriptSrc = [];

    
    protected $styleSrc = [];

    
    protected $sandbox = [];

    
    protected $reportURI;

    
    protected ?string $reportTo = null;

    
    
    

    
    protected $manifestSrc = [];

    
    protected array|string $scriptSrcElem = [];

    
    protected array|string $scriptSrcAttr = [];

    
    protected array|string $styleSrcElem = [];

    
    protected array|string $styleSrcAttr = [];

    
    protected array|string $workerSrc = [];

    
    protected $upgradeInsecureRequests = false;

    
    protected $reportOnly = false;

    
    protected $validSources = [
        
        'self',
        'none',
        'unsafe-inline',
        'unsafe-eval',
        
        'strict-dynamic',
        'unsafe-hashes',
        'report-sample',
        'unsafe-allow-redirects',
        'wasm-unsafe-eval',
        'trusted-types-eval',
        'report-sha256',
        'report-sha384',
        'report-sha512',
    ];

    
    protected $nonces = [];

    
    protected $styleNonce;

    
    protected $scriptNonce;

    
    protected $styleNonceTag = '{csp-style-nonce}';

    
    protected $scriptNonceTag = '{csp-script-nonce}';

    
    protected $autoNonce = true;

    
    protected $tempHeaders = [];

    
    protected $reportOnlyHeaders = [];

    
    protected $CSPEnabled = false;

    
    private array $reportingEndpoints = [];

    
    public function __construct(ContentSecurityPolicyConfig $config)
    {
        $this->CSPEnabled = config(App::class)->CSPEnabled;

        foreach (get_object_vars($config) as $setting => $value) {
            if (! property_exists($this, $setting)) {
                continue;
            }

            if (
                in_array($setting, self::DIRECTIVES_ALLOWING_SOURCE_LISTS, true)
                && is_array($value)
                && array_is_list($value)
            ) {
                
                
                $this->{$setting} = array_combine($value, array_fill(0, count($value), $this->reportOnly));

                continue;
            }

            $this->{$setting} = $value;
        }

        if (! is_array($this->styleSrc)) {
            $this->styleSrc = [$this->styleSrc => $this->reportOnly];
        }

        if (! is_array($this->scriptSrc)) {
            $this->scriptSrc = [$this->scriptSrc => $this->reportOnly];
        }
    }

    
    public function enabled(): bool
    {
        return $this->CSPEnabled;
    }

    
    public function getStyleNonce(): string
    {
        if ($this->styleNonce === null) {
            $this->styleNonce = base64_encode(random_bytes(12));
            $this->addStyleSrc('nonce-' . $this->styleNonce);

            if ($this->styleSrcElem !== []) {
                $this->addStyleSrcElem('nonce-' . $this->styleNonce);
            }
        }

        return $this->styleNonce;
    }

    
    public function getScriptNonce(): string
    {
        if ($this->scriptNonce === null) {
            $this->scriptNonce = base64_encode(random_bytes(12));
            $this->addScriptSrc('nonce-' . $this->scriptNonce);

            if ($this->scriptSrcElem !== []) {
                $this->addScriptSrcElem('nonce-' . $this->scriptNonce);
            }
        }

        return $this->scriptNonce;
    }

    
    public function finalize(ResponseInterface $response)
    {
        $this->generateNonces($response);

        $this->buildHeaders($response);
    }

    
    public function reportOnly(bool $value = true)
    {
        $this->reportOnly = $value;

        return $this;
    }

    
    public function addBaseURI($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'baseURI', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addChildSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'childSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addConnectSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'connectSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function setDefaultSrc($uri, ?bool $explicitReporting = null)
    {
        $this->defaultSrc = [(string) $uri => $explicitReporting ?? $this->reportOnly];

        return $this;
    }

    
    public function addFontSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'fontSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addFormAction($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'formAction', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addFrameAncestor($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'frameAncestors', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addFrameSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'frameSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addImageSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'imageSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addMediaSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'mediaSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addManifestSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'manifestSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addObjectSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'objectSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addPluginType($mime, ?bool $explicitReporting = null)
    {
        $this->addOption($mime, 'pluginTypes', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addSandbox($flags, ?bool $explicitReporting = null)
    {
        $this->addOption($flags, 'sandbox', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addScriptSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'scriptSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addScriptSrcElem(array|string $uri, ?bool $explicitReporting = null): static
    {
        $this->addOption($uri, 'scriptSrcElem', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addScriptSrcAttr(array|string $uri, ?bool $explicitReporting = null): static
    {
        $this->addOption($uri, 'scriptSrcAttr', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addStyleSrc($uri, ?bool $explicitReporting = null)
    {
        $this->addOption($uri, 'styleSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addStyleSrcElem(array|string $uri, ?bool $explicitReporting = null): static
    {
        $this->addOption($uri, 'styleSrcElem', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addStyleSrcAttr(array|string $uri, ?bool $explicitReporting = null): static
    {
        $this->addOption($uri, 'styleSrcAttr', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function addWorkerSrc($uri, ?bool $explicitReporting = null): static
    {
        $this->addOption($uri, 'workerSrc', $explicitReporting ?? $this->reportOnly);

        return $this;
    }

    
    public function upgradeInsecureRequests(bool $value = true)
    {
        $this->upgradeInsecureRequests = $value;

        return $this;
    }

    
    public function setReportURI(string $uri)
    {
        $this->reportURI = $uri;

        return $this;
    }

    
    public function setReportToEndpoint(string $endpoint): static
    {
        if ($endpoint === '') {
            $this->reportURI = null;
            $this->reportTo  = null;

            return $this;
        }

        if (! array_key_exists($endpoint, $this->reportingEndpoints)) {
            throw new InvalidArgumentException(sprintf('The reporting endpoint "%s" has not been defined.', $endpoint));
        }

        $this->reportURI = $this->reportingEndpoints[$endpoint]; 
        $this->reportTo  = $endpoint;

        return $this;
    }

    
    public function addReportingEndpoints(array $endpoint): static
    {
        foreach ($endpoint as $name => $url) {
            $this->reportingEndpoints[$name] = $url;
        }

        return $this;
    }

    
    protected function addOption($options, string $target, ?bool $explicitReporting = null)
    {
        
        if (is_string($this->{$target})) {
            $this->{$target} = [$this->{$target} => $this->reportOnly];
        }

        $options = is_array($options) ? $options : [$options];

        foreach ($options as $option) {
            $this->{$target}[$option] = $explicitReporting ?? $this->reportOnly;
        }
    }

    
    protected function generateNonces(ResponseInterface $response)
    {
        if ($this->enabled() && ! $this->autoNonce) {
            return;
        }

        $body = (string) $response->getBody();

        if ($body === '') {
            return;
        }

        
        $jsonEscape = str_contains($response->getHeaderLine('Content-Type'), 'json');

        
        $pattern = sprintf('/(%s|%s)/', preg_quote($this->styleNonceTag, '/'), preg_quote($this->scriptNonceTag, '/'));

        $body = preg_replace_callback($pattern, function ($match) use ($jsonEscape): string {
            if (! $this->enabled()) {
                return '';
            }

            $nonce = $match[0] === $this->styleNonceTag ? $this->getStyleNonce() : $this->getScriptNonce();
            $attr  = 'nonce="' . $nonce . '"';

            return $jsonEscape ? str_replace('"', '\\"', $attr) : $attr;
        }, $body);

        $response->setBody($body);
    }

    
    protected function buildHeaders(ResponseInterface $response)
    {
        if (! $this->enabled()) {
            return;
        }

        $response->setHeader('Content-Security-Policy', []);
        $response->setHeader('Content-Security-Policy-Report-Only', []);
        $response->setHeader('Reporting-Endpoints', []);

        if (in_array($this->baseURI, ['', null, []], true)) {
            $this->baseURI = 'self';
        }

        if (in_array($this->defaultSrc, ['', null, []], true)) {
            $this->defaultSrc = 'self';
        }

        foreach ($this->directives as $name => $property) {
            if ($name === 'report-uri' && (string) $this->reportURI === '') {
                continue;
            }

            if ($name === 'report-to' && (string) $this->reportTo === '') {
                continue;
            }

            if ($this->{$property} !== null) {
                $this->addToHeader($name, $this->{$property});
            }
        }

        
        
        
        if ($this->reportingEndpoints !== []) {
            $endpoints = [];

            foreach ($this->reportingEndpoints as $name => $url) {
                $endpoints[] = trim("{$name}=\"{$url}\"");
            }

            $response->appendHeader('Reporting-Endpoints', implode(', ', $endpoints));
            $this->reportingEndpoints = [];
        }

        if ($this->tempHeaders !== []) {
            $header = [];

            foreach ($this->tempHeaders as $name => $value) {
                $header[] = trim("{$name} {$value}");
            }

            if ($this->upgradeInsecureRequests) {
                $header[] = 'upgrade-insecure-requests';
            }

            $response->appendHeader('Content-Security-Policy', implode('; ', $header));
            $this->tempHeaders = [];
        }

        if ($this->reportOnlyHeaders !== []) {
            $header = [];

            foreach ($this->reportOnlyHeaders as $name => $value) {
                $header[] = trim("{$name} {$value}");
            }

            $response->appendHeader('Content-Security-Policy-Report-Only', implode('; ', $header));
            $this->reportOnlyHeaders = [];
        }
    }

    
    protected function addToHeader(string $name, $values = null)
    {
        if (is_string($values)) {
            $values = [$values => $this->reportOnly];
        }

        $sources       = [];
        $reportSources = [];

        foreach ($values as $value => $reportOnly) {
            if (
                in_array($value, $this->validSources, true)
                || str_starts_with($value, 'nonce-')
                || str_starts_with($value, 'sha256-')
                || str_starts_with($value, 'sha384-')
                || str_starts_with($value, 'sha512-')
            ) {
                $value = "'{$value}'";
            }

            if ($reportOnly) {
                $reportSources[] = $value;
            } else {
                $sources[] = $value;
            }
        }

        if ($sources !== []) {
            $this->tempHeaders[$name] = implode(' ', $sources);
        }

        if ($reportSources !== []) {
            $this->reportOnlyHeaders[$name] = implode(' ', $reportSources);
        }
    }

    public function clearDirective(string $directive): void
    {
        if (! array_key_exists($directive, $this->directives)) {
            return;
        }

        if ($directive === 'report-uri') {
            $this->reportURI = null;

            return;
        }

        if ($directive === 'report-to') {
            $this->reportURI = null;
            $this->reportTo  = null;

            return;
        }

        $this->{$this->directives[$directive]} = [];
    }
}
