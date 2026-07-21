<?php

declare(strict_types=1);



namespace CodeIgniter\HTTP;

use CodeIgniter\Exceptions\ConfigException;
use CodeIgniter\Validation\FormatRules;
use Config\App;


trait RequestTrait
{
    
    protected $config;

    
    protected $ipAddress = '';

    
    protected $globals = [];

    
    public function getIPAddress(): string
    {
        if ($this->ipAddress !== '') {
            return $this->ipAddress;
        }

        $ipValidator = [
            new FormatRules(),
            'valid_ip',
        ];

        $proxyIPs = $this->config->proxyIPs;

        if (! empty($proxyIPs) && (! is_array($proxyIPs) || is_int(array_key_first($proxyIPs)))) {
            throw new ConfigException(
                'You must set an array with Proxy IP address key and HTTP header name value in Config\App::$proxyIPs.',
            );
        }

        $this->ipAddress = $this->getServer('REMOTE_ADDR');

        
        if ($this->ipAddress === null) {
            return $this->ipAddress = '0.0.0.0';
        }

        
        foreach ($proxyIPs as $proxyIP => $header) {
            
            if (! str_contains($proxyIP, '/')) {
                
                
                if ($proxyIP === $this->ipAddress) {
                    $spoof = $this->getClientIP($header);

                    if ($spoof !== null) {
                        $this->ipAddress = $spoof;
                        break;
                    }
                }

                continue;
            }

            
            if (! isset($separator)) {
                $separator = $ipValidator($this->ipAddress, 'ipv6') ? ':' : '.';
            }

            
            if (! str_contains($proxyIP, $separator)) {
                continue;
            }

            
            if (! isset($ip, $sprintf)) {
                if ($separator === ':') {
                    
                    $ip = explode(':', str_replace('::', str_repeat(':', 9 - substr_count($this->ipAddress, ':')), $this->ipAddress));

                    for ($j = 0; $j < 8; $j++) {
                        $ip[$j] = intval($ip[$j], 16);
                    }

                    $sprintf = '%016b%016b%016b%016b%016b%016b%016b%016b';
                } else {
                    $ip      = explode('.', $this->ipAddress);
                    $sprintf = '%08b%08b%08b%08b';
                }

                $ip = vsprintf($sprintf, $ip);
            }

            
            sscanf($proxyIP, '%[^/]/%d', $netaddr, $masklen);

            
            if ($separator === ':') {
                $netaddr = explode(':', str_replace('::', str_repeat(':', 9 - substr_count($netaddr, ':')), $netaddr));

                for ($i = 0; $i < 8; $i++) {
                    $netaddr[$i] = intval($netaddr[$i], 16);
                }
            } else {
                $netaddr = explode('.', $netaddr);
            }

            
            if (strncmp($ip, vsprintf($sprintf, $netaddr), $masklen) === 0) {
                $spoof = $this->getClientIP($header);

                if ($spoof !== null) {
                    $this->ipAddress = $spoof;
                    break;
                }
            }
        }

        if (! $ipValidator($this->ipAddress)) {
            return $this->ipAddress = '0.0.0.0';
        }

        return $this->ipAddress;
    }

    
    private function getClientIP(string $header): ?string
    {
        $ipValidator = [
            new FormatRules(),
            'valid_ip',
        ];
        $spoof     = null;
        $headerObj = $this->header($header);

        if ($headerObj !== null) {
            $spoof = $headerObj->getValue();

            
            
            
            sscanf($spoof, '%[^,]', $spoof);

            if (! $ipValidator($spoof)) {
                $spoof = null;
            }
        }

        return $spoof;
    }

    
    public function getServer($index = null, $filter = null, $flags = null)
    {
        return $this->fetchGlobal('server', $index, $filter, $flags);
    }

    
    public function getEnv($index = null, $filter = null, $flags = null)
    {
        
        return $this->fetchGlobal('env', $index, $filter, $flags);
    }

    
    public function setGlobal(string $name, $value)
    {
        
        $this->globals[$name] = $value;

        
        service('superglobals')->setGlobalArray($name, $value);

        return $this;
    }

    
    public function fetchGlobal(string $name, $index = null, ?int $filter = null, $flags = null)
    {
        if (! isset($this->globals[$name])) {
            $this->populateGlobals($name);
        }

        
        $filter ??= FILTER_UNSAFE_RAW;
        $flags = is_array($flags) ? $flags : (is_numeric($flags) ? (int) $flags : 0);

        
        if ($index === null) {
            $values = [];

            foreach ($this->globals[$name] as $key => $value) {
                $values[$key] = is_array($value)
                    ? $this->fetchGlobal($name, $key, $filter, $flags)
                    : filter_var($value, $filter, $flags);
            }

            return $values;
        }

        
        if (is_array($index)) {
            $output = [];

            foreach ($index as $key) {
                $output[$key] = $this->fetchGlobal($name, $key, $filter, $flags);
            }

            return $output;
        }

        
        if (is_string($index) && ($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) {
            $value = $this->globals[$name];

            for ($i = 0; $i < $count; $i++) {
                $key = trim($matches[0][$i], '[]');

                if ($key === '') { 
                    break;
                }

                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return null;
                }
            }
        }

        if (! isset($value)) {
            $value = $this->globals[$name][$index] ?? null;
        }

        if (is_array($value)
            && (
                $filter !== FILTER_UNSAFE_RAW
                || (
                    (is_numeric($flags) && $flags !== 0)
                    || is_array($flags) && $flags !== []
                )
            )
        ) {
            
            array_walk_recursive($value, static function (&$val) use ($filter, $flags): void {
                $val = filter_var($val, $filter, $flags);
            });

            return $value;
        }

        
        if (is_array($value) || is_object($value) || $value === null) {
            return $value;
        }

        return filter_var($value, $filter, $flags);
    }

    
    protected function populateGlobals(string $name)
    {
        if (! isset($this->globals[$name])) {
            $this->globals[$name] = [];
        }

        
        $this->globals[$name] = service('superglobals')->getGlobalArray($name);
    }
}
