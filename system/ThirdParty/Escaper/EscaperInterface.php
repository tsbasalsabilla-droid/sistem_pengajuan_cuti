<?php

declare(strict_types=1);

namespace Laminas\Escaper;


interface EscaperInterface
{
    
    public function escapeHtml(string $string);

    
    public function escapeHtmlAttr(string $string);

    
    public function escapeJs(string $string);

    
    public function escapeUrl(string $string);

    
    public function escapeCss(string $string);
}
