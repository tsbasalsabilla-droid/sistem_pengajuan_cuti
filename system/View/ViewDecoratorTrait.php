<?php

declare(strict_types=1);



namespace CodeIgniter\View;

use CodeIgniter\View\Exceptions\ViewException;
use Config\View as ViewConfig;

trait ViewDecoratorTrait
{
    
    protected function decorateOutput(string $html): string
    {
        $decorators = $this->config->decorators ?? config(ViewConfig::class)->decorators;

        foreach ($decorators as $decorator) {
            if (! is_subclass_of($decorator, ViewDecoratorInterface::class)) {
                throw ViewException::forInvalidDecorator($decorator);
            }

            $html = $decorator::decorate($html);
        }

        return $html;
    }
}
