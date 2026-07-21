<?php

declare(strict_types=1);



namespace CodeIgniter\View\Cells;

use CodeIgniter\Exceptions\LogicException;
use CodeIgniter\Traits\PropertiesTrait;
use ReflectionClass;
use Stringable;


class Cell implements Stringable
{
    use PropertiesTrait;

    
    protected string $view = '';

    
    public function render(): string
    {
        if (! function_exists('decamelize')) {
            helper('inflector');
        }

        return $this->view($this->view);
    }

    
    public function setView(string $view)
    {
        $this->view = $view;

        return $this;
    }

    
    final protected function view(?string $view, array $data = []): string
    {
        $properties = $this->getPublicProperties();
        $properties = $this->includeComputedProperties($properties);
        $properties = array_merge($properties, $data);

        $view = (string) $view;

        if ($view === '') {
            $viewName  = decamelize(class_basename(static::class));
            $directory = dirname((new ReflectionClass($this))->getFileName()) . DIRECTORY_SEPARATOR;

            $possibleView1 = $directory . substr($viewName, 0, (int) strrpos($viewName, '_cell')) . '.php';
            $possibleView2 = $directory . $viewName . '.php';
        }

        if ($view !== '' && ! is_file($view)) {
            $directory = dirname((new ReflectionClass($this))->getFileName()) . DIRECTORY_SEPARATOR;

            $view = $directory . $view . '.php';
        }

        $candidateViews = array_filter(
            [$view, $possibleView1 ?? '', $possibleView2 ?? ''],
            static fn (string $path): bool => $path !== '' && is_file($path),
        );

        if ($candidateViews === []) {
            throw new LogicException(sprintf(
                'Cannot locate the view file for the "%s" cell.',
                static::class,
            ));
        }

        $foundView = current($candidateViews);

        return (function () use ($properties, $foundView): string {
            extract($properties);
            ob_start();
            include $foundView;

            return ob_get_clean();
        })();
    }

    
    public function __toString(): string
    {
        return $this->render();
    }

    
    private function includeComputedProperties(array $properties): array
    {
        $reservedProperties = ['data', 'view'];
        $privateProperties  = $this->getNonPublicProperties();

        foreach ($privateProperties as $property) {
            $name = $property->getName();

            
            if (in_array($name, $reservedProperties, true)) {
                continue;
            }

            $computedMethod = 'get' . ucfirst($name) . 'Property';

            if (method_exists($this, $computedMethod)) {
                $properties[$name] = $this->{$computedMethod}();
            }
        }

        return $properties;
    }
}
