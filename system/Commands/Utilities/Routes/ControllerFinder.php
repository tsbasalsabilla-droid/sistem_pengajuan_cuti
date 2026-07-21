<?php

declare(strict_types=1);



namespace CodeIgniter\Commands\Utilities\Routes;

use CodeIgniter\Autoloader\FileLocatorInterface;


final  class ControllerFinder
{
    private FileLocatorInterface $locator;

    
    public function __construct(
        private string $namespace,
    ) {
        $this->locator = service('locator');
    }

    
    public function find(): array
    {
        $nsArray = explode('\\', trim($this->namespace, '\\'));
        $count   = count($nsArray);
        $ns      = '';
        $files   = [];

        for ($i = 0; $i < $count; $i++) {
            $ns .= '\\' . array_shift($nsArray);
            $path = implode('\\', $nsArray);

            $files = $this->locator->listNamespaceFiles($ns, $path);

            if ($files !== []) {
                break;
            }
        }

        $classes = [];

        foreach ($files as $file) {
            if (\is_file($file)) {
                $classnameOrEmpty = $this->locator->getClassname($file);

                if ($classnameOrEmpty !== '') {
                    
                    $classname = $classnameOrEmpty;

                    $classes[] = $classname;
                }
            }
        }

        return $classes;
    }
}
