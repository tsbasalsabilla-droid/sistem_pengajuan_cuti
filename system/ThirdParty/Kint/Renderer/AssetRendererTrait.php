<?php

declare(strict_types=1);



namespace Kint\Renderer;

trait AssetRendererTrait
{
    public static ?string $theme = null;

    
    private static array $assetCache = [];

    
    public static function renderJs(): string
    {
        if (!isset(self::$assetCache['js'])) {
            self::$assetCache['js'] = \file_get_contents(KINT_DIR.'/resources/compiled/main.js');
        }

        return self::$assetCache['js'];
    }

    
    public static function renderCss(): ?string
    {
        if (!isset(self::$theme)) {
            return null;
        }

        if (!isset(self::$assetCache['css'][self::$theme])) {
            if (\file_exists(KINT_DIR.'/resources/compiled/'.self::$theme)) {
                self::$assetCache['css'][self::$theme] = \file_get_contents(KINT_DIR.'/resources/compiled/'.self::$theme);
            } elseif (\file_exists(self::$theme)) {
                self::$assetCache['css'][self::$theme] = \file_get_contents(self::$theme);
            } else {
                self::$assetCache['css'][self::$theme] = false;
            }
        }

        if (false === self::$assetCache['css'][self::$theme]) {
            return null;
        }

        return self::$assetCache['css'][self::$theme];
    }
}
