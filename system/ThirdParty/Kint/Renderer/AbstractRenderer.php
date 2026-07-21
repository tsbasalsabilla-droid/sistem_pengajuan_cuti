<?php

declare(strict_types=1);



namespace Kint\Renderer;

abstract class AbstractRenderer implements ConstructableRendererInterface
{
    public static ?string $js_nonce = null;
    public static ?string $css_nonce = null;

    
    public static ?string $file_link_format = null;

    protected bool $show_trace = true;
    protected ?array $callee = null;
    protected array $trace = [];

    protected bool $render_spl_ids = true;

    public function __construct()
    {
    }

    public function shouldRenderObjectIds(): bool
    {
        return $this->render_spl_ids;
    }

    public function setCallInfo(array $info): void
    {
        $this->callee = $info['callee'] ?? null;
        $this->trace = $info['trace'] ?? [];
    }

    public function setStatics(array $statics): void
    {
        $this->show_trace = !empty($statics['display_called_from']);
    }

    public function filterParserPlugins(array $plugins): array
    {
        return $plugins;
    }

    public function preRender(): string
    {
        return '';
    }

    public function postRender(): string
    {
        return '';
    }

    public static function getFileLink(string $file, int $line): ?string
    {
        if (null === self::$file_link_format) {
            return null;
        }

        return \str_replace(['%f', '%l'], [$file, $line], self::$file_link_format);
    }
}
