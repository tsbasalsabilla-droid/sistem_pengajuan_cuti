<?php

declare(strict_types=1);



namespace CodeIgniter\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;


class PerformanceMetrics implements FilterInterface
{
    
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $body = $response->getBody();

        if ($body !== null) {
            $benchmark = service('timer');

            $output = str_replace(
                [
                    '{elapsed_time}',
                    '{memory_usage}',
                ],
                [
                    (string) $benchmark->getElapsedTime('total_execution'),
                    number_format(memory_get_peak_usage() / 1024 / 1024, 3),
                ],
                $body,
            );

            $response->setBody($output);

            return $response;
        }

        return null;
    }
}
