<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\HTTP\Exceptions\HTTPException;
use Config\Feature;


class Negotiate
{
    
    protected $request;

    
    public function __construct(?RequestInterface $request = null)
    {
        if ($request instanceof RequestInterface) {
            assert($request instanceof IncomingRequest);

            $this->request = $request;
        }
    }

    
    public function setRequest(RequestInterface $request)
    {
        assert($request instanceof IncomingRequest);

        $this->request = $request;

        return $this;
    }

    
    public function media(array $supported, bool $strictMatch = false): string
    {
        return $this->getBestMatch($supported, $this->request->getHeaderLine('accept'), true, $strictMatch);
    }

    
    public function charset(array $supported): string
    {
        $match = $this->getBestMatch(
            $supported,
            $this->request->getHeaderLine('accept-charset'),
            false,
            true,
        );

        
        
        if ($match === '') {
            return 'utf-8';
        }

        return $match;
    }

    
    public function encoding(array $supported = []): string
    {
        $supported[] = 'identity';

        return $this->getBestMatch($supported, $this->request->getHeaderLine('accept-encoding'));
    }

    
    public function language(array $supported): string
    {
        if (config(Feature::class)->strictLocaleNegotiation) {
            return $this->getBestLocaleMatch($supported, $this->request->getHeaderLine('accept-language'));
        }

        return $this->getBestMatch($supported, $this->request->getHeaderLine('accept-language'), false, false, true);
    }

    
    
    

    
    protected function getBestMatch(
        array $supported,
        ?string $header = null,
        bool $enforceTypes = false,
        bool $strictMatch = false,
        bool $matchLocales = false,
    ): string {
        if ($supported === []) {
            throw HTTPException::forEmptySupportedNegotiations();
        }

        if ($header === null || $header === '') {
            return $strictMatch ? '' : $supported[0];
        }

        $acceptable = $this->parseHeader($header);

        foreach ($acceptable as $accept) {
            
            if ($accept['q'] === 0.0) {
                continue;
            }

            
            if ($accept['value'] === '*' || $accept['value'] === '*/*') {
                return $supported[0];
            }

            
            foreach ($supported as $available) {
                if ($this->match($accept, $available, $enforceTypes, $matchLocales)) {
                    return $available;
                }
            }
        }

        
        return $strictMatch ? '' : $supported[0];
    }

    
    protected function getBestLocaleMatch(array $supportedLocales, ?string $header): string
    {
        if ($supportedLocales === []) {
            throw HTTPException::forEmptySupportedNegotiations();
        }

        if ($header === null || $header === '') {
            return $supportedLocales[0];
        }

        $acceptable      = $this->parseHeader($header);
        $fallbackLocales = [];

        foreach ($acceptable as $accept) {
            
            if ($accept['q'] === 0.0) {
                continue;
            }

            
            if ($accept['value'] === '*') {
                return $supportedLocales[0];
            }

            
            if (in_array($accept['value'], $supportedLocales, true)) {
                return $accept['value'];
            }

            
            $fallbackLocales[] = strtok($accept['value'], '-');
        }

        foreach ($fallbackLocales as $fallbackLocale) {
            
            if (in_array($fallbackLocale, $supportedLocales, true)) {
                return $fallbackLocale;
            }

            
            foreach ($supportedLocales as $locale) {
                if (str_starts_with($locale, $fallbackLocale . '-')) {
                    return $locale;
                }
            }
        }

        return $supportedLocales[0];
    }

    
    public function parseHeader(string $header): array
    {
        $results    = [];
        $acceptable = explode(',', $header);

        foreach ($acceptable as $value) {
            $pairs = explode(';', $value);

            $value = $pairs[0];

            unset($pairs[0]);

            $parameters = [];

            foreach ($pairs as $pair) {
                if (preg_match(
                    '/^(?P<name>.+?)=(?P<quoted>"|\')?(?P<value>.*?)(?:\k<quoted>)?$/',
                    $pair,
                    $param,
                )) {
                    $parameters[trim($param['name'])] = trim($param['value']);
                }
            }

            $quality = 1.0;

            if (array_key_exists('q', $parameters)) {
                $quality = $parameters['q'];
                unset($parameters['q']);
            }

            $results[] = [
                'value'  => trim($value),
                'q'      => (float) $quality,
                'params' => $parameters,
            ];
        }

        
        usort($results, static function ($a, $b): int {
            if ($a['q'] === $b['q']) {
                $aAst = substr_count($a['value'], '*');
                $bAst = substr_count($b['value'], '*');

                
                
                
                
                
                
                if ($aAst > $bAst) {
                    return 1;
                }

                
                
                
                
                
                
                if ($aAst === $bAst) {
                    return count($b['params']) - count($a['params']);
                }

                return 0;
            }

            
            return ($a['q'] > $b['q']) ? -1 : 1;
        });

        return $results;
    }

    
    protected function match(array $acceptable, string $supported, bool $enforceTypes = false, $matchLocales = false): bool
    {
        $supported = $this->parseHeader($supported);
        if (count($supported) === 1) {
            $supported = $supported[0];
        }

        
        if ($acceptable['value'] === $supported['value']) {
            return $this->matchParameters($acceptable, $supported);
        }

        
        
        if ($enforceTypes) {
            return $this->matchTypes($acceptable, $supported);
        }

        
        if ($matchLocales) {
            return $this->matchLocales($acceptable, $supported);
        }

        return false;
    }

    
    protected function matchParameters(array $acceptable, array $supported): bool
    {
        if (count($acceptable['params']) !== count($supported['params'])) {
            return false;
        }

        foreach ($supported['params'] as $label => $value) {
            if (! isset($acceptable['params'][$label])
                || $acceptable['params'][$label] !== $value
            ) {
                return false;
            }
        }

        return true;
    }

    
    public function matchTypes(array $acceptable, array $supported): bool
    {
        
        
        [$aType, $aSubType] = explode('/', $acceptable['value']);
        [$sType, $sSubType] = explode('/', $supported['value']);

        
        if ($aType !== $sType) {
            return false;
        }

        
        if ($aSubType === '*') {
            return true;
        }

        
        return $aSubType === $sSubType;
    }

    
    public function matchLocales(array $acceptable, array $supported): bool
    {
        $aBroad = mb_strpos($acceptable['value'], '-') > 0
            ? mb_substr($acceptable['value'], 0, mb_strpos($acceptable['value'], '-'))
            : $acceptable['value'];
        $sBroad = mb_strpos($supported['value'], '-') > 0
            ? mb_substr($supported['value'], 0, mb_strpos($supported['value'], '-'))
            : $supported['value'];

        return strtolower($aBroad) === strtolower($sBroad);
    }
}
