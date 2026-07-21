<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities\Routes;


final  class AutoRouteCollector
{
    
    public function __construct(private string $namespace, private string $defaultController, private string $defaultMethod)
    {
    }

    
    public function get(): array
    {
        $finder = new ControllerFinder($this->namespace);
        $reader = new ControllerMethodReader($this->namespace);

        $tbody = [];

        foreach ($finder->find() as $class) {
            $output = $reader->read(
                $class,
                $this->defaultController,
                $this->defaultMethod,
            );

            foreach ($output as $item) {
                $tbody[] = [
                    'auto',
                    $item['route'],
                    '',
                    $item['handler'],
                ];
            }
        }

        return $tbody;
    }
}
