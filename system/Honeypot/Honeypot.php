<?php

declare(strict_types=1);



namespace CodeIgniter\Honeypot;

use CodeIgniter\Honeypot\Exceptions\HoneypotException;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Honeypot as HoneypotConfig;


class Honeypot
{
    
    protected $config;

    
    public function __construct(HoneypotConfig $config)
    {
        $this->config = $config;

        if ($this->config->container === '' || ! str_contains($this->config->container, '{template}')) {
            $this->config->container = '<div style="display:none">{template}</div>';
        }

        $this->config->containerId ??= 'hpc';

        if ($this->config->template === '') {
            throw HoneypotException::forNoTemplate();
        }

        if ($this->config->name === '') {
            throw HoneypotException::forNoNameField();
        }
    }

    
    public function hasContent(RequestInterface $request)
    {
        assert($request instanceof IncomingRequest);

        return ! empty($request->getPost($this->config->name));
    }

    
    public function attachHoneypot(ResponseInterface $response)
    {
        if ($response->getBody() === null) {
            return;
        }

        if ($response->getCSP()->enabled()) {
            
            $this->config->container = str_ireplace(
                '>{template}',
                ' id="' . $this->config->containerId . '">{template}',
                $this->config->container,
            );
        }

        $prepField = $this->prepareTemplate($this->config->template);

        $bodyBefore = $response->getBody();
        $bodyAfter  = str_ireplace('</form>', $prepField . '</form>', $bodyBefore);

        if ($response->getCSP()->enabled() && ($bodyBefore !== $bodyAfter)) {
            
            $style     = '<style ' . csp_style_nonce() . '>#' . $this->config->containerId . ' { display:none }</style>';
            $bodyAfter = str_ireplace('</head>', $style . '</head>', $bodyAfter);
        }

        $response->setBody($bodyAfter);
    }

    
    protected function prepareTemplate(string $template): string
    {
        $template = str_ireplace('{label}', $this->config->label, $template);
        $template = str_ireplace('{name}', $this->config->name, $template);

        if ($this->config->hidden) {
            $template = str_ireplace('{template}', $template, $this->config->container);
        }

        return $template;
    }
}
