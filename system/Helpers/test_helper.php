<?php

declare(strict_types=1);



use CodeIgniter\Exceptions\TestException;
use CodeIgniter\Model;
use CodeIgniter\Test\Fabricator;
use Config\Services;



if (! function_exists('fake')) {
    
    function fake($model, ?array $overrides = null, $persist = true)
    {
        $fabricator = new Fabricator($model);

        if ($overrides !== null) {
            $fabricator->setOverrides($overrides);
        }

        if ($persist) {
            return $fabricator->create();
        }

        return $fabricator->make();
    }
}

if (! function_exists('mock')) {
    
    function mock(string $className)
    {
        $mockClass   = $className::$mockClass;
        $mockService = $className::$mockServiceName ?? '';

        if (empty($mockClass) || ! class_exists($mockClass)) {
            throw TestException::forInvalidMockClass($mockClass);
        }

        $mock = new $mockClass();

        if (! empty($mockService)) {
            Services::injectMock($mockService, $mock);
        }

        return $mock;
    }
}
