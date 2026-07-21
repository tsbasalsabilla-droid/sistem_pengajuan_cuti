<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\URI;


class MockCURLRequest extends CURLRequest
{
    
    public $curl_options;

    
    protected $output = '';

    
    public function setOutput($output)
    {
        $this->output = $output;

        return $this;
    }

    
    protected function sendRequest(array $curlOptions = []): string
    {
        $this->response = clone $this->responseOrig;

        $this->curl_options = $curlOptions;

        return $this->output;
    }

    
    public function getBaseURI()
    {
        return $this->baseURI;
    }

    
    public function getDelay()
    {
        return $this->delay;
    }
}
