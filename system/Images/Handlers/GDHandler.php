<?php

declare(strict_types=1);



namespace CodeIgniter\Images\Handlers;

use CodeIgniter\Images\Exceptions\ImageException;
use Config\Images;


class GDHandler extends BaseHandler
{
    
    public function __construct($config = null)
    {
        parent::__construct($config);

        if (! extension_loaded('gd')) {
            throw ImageException::forMissingExtension('GD'); 
        }
    }

    
    protected function _rotate(int $angle): bool
    {
        
        $srcImg = $this->createImage();

        
        
        
        

        $white = imagecolorallocate($srcImg, 255, 255, 255);

        
        $destImg = imagerotate($srcImg, $angle, $white);

        $this->resource = $destImg;

        return true;
    }

    
    protected function _flatten(int $red = 255, int $green = 255, int $blue = 255)
    {
        $srcImg = $this->createImage();

        if (function_exists('imagecreatetruecolor')) {
            $create = 'imagecreatetruecolor';
            $copy   = 'imagecopyresampled';
        } else {
            $create = 'imagecreate';
            $copy   = 'imagecopyresized';
        }
        $dest = $create($this->width, $this->height);

        $matte = imagecolorallocate($dest, $red, $green, $blue);

        imagefilledrectangle($dest, 0, 0, $this->width, $this->height, $matte);
        imagecopy($dest, $srcImg, 0, 0, 0, 0, $this->width, $this->height);

        $this->resource = $dest;

        return $this;
    }

    
    protected function _flip(string $direction)
    {
        $srcImg = $this->createImage();

        $angle = $direction === 'horizontal' ? IMG_FLIP_HORIZONTAL : IMG_FLIP_VERTICAL;

        imageflip($srcImg, $angle);

        $this->resource = $srcImg;

        return $this;
    }

    
    public function getVersion()
    {
        if (function_exists('gd_info')) {
            $gdVersion = @gd_info();

            return preg_replace('/\D/', '', $gdVersion['GD Version']);
        }

        return false;
    }

    
    public function _resize(bool $maintainRatio = false)
    {
        return $this->process('resize');
    }

    
    public function _crop()
    {
        return $this->process('crop');
    }

    
    protected function process(string $action)
    {
        $origWidth  = $this->image()->origWidth;
        $origHeight = $this->image()->origHeight;

        if ($action === 'crop') {
            
            $origWidth  = $this->width;
            $origHeight = $this->height;

            
            
            
            $this->image()->origHeight = $this->height;
            $this->image()->origWidth  = $this->width;
        }

        
        $src = $this->createImage();

        if (function_exists('imagecreatetruecolor')) {
            $create = 'imagecreatetruecolor';
            $copy   = 'imagecopyresampled';
        } else {
            $create = 'imagecreate';
            $copy   = 'imagecopyresized';
        }

        $dest = $create($this->width, $this->height);

        
        if (in_array($this->image()->imageType, $this->supportTransparency, true)) {
            imagealphablending($dest, false);
            imagesavealpha($dest, true);
        }

        $copy($dest, $src, 0, 0, (int) $this->xAxis, (int) $this->yAxis, $this->width, $this->height, $origWidth, $origHeight);

        $this->resource = $dest;

        return $this;
    }

    
    public function save(?string $target = null, int $quality = 90): bool
    {
        $original = $target;
        $target   = ($target === null || $target === '') ? $this->image()->getPathname() : $target;

        
        
        if (empty($this->resource) && $quality === 100) {
            if ($original === null) {
                return true;
            }

            $name = basename($target);
            $path = pathinfo($target, PATHINFO_DIRNAME);

            return $this->image()->copy($path, $name);
        }

        $this->ensureResource();

        
        if (in_array($this->image()->imageType, $this->supportTransparency, true)) {
            imagepalettetotruecolor($this->resource);
            imagealphablending($this->resource, false);
            imagesavealpha($this->resource, true);
        }

        switch ($this->image()->imageType) {
            case IMAGETYPE_GIF:
                if (! function_exists('imagegif')) {
                    throw ImageException::forInvalidImageCreate(lang('Images.gifNotSupported'));
                }

                if (! @imagegif($this->resource, $target)) {
                    throw ImageException::forSaveFailed();
                }
                break;

            case IMAGETYPE_JPEG:
                if (! function_exists('imagejpeg')) {
                    throw ImageException::forInvalidImageCreate(lang('Images.jpgNotSupported'));
                }

                if (! @imagejpeg($this->resource, $target, $quality)) {
                    throw ImageException::forSaveFailed();
                }
                break;

            case IMAGETYPE_PNG:
                if (! function_exists('imagepng')) {
                    throw ImageException::forInvalidImageCreate(lang('Images.pngNotSupported'));
                }

                if (! @imagepng($this->resource, $target)) {
                    throw ImageException::forSaveFailed();
                }
                break;

            case IMAGETYPE_WEBP:
                if (! function_exists('imagewebp')) {
                    throw ImageException::forInvalidImageCreate(lang('Images.webpNotSupported'));
                }

                if (! @imagewebp($this->resource, $target, $quality)) {
                    throw ImageException::forSaveFailed();
                }
                break;

            default:
                throw ImageException::forInvalidImageCreate();
        }

        $this->resource = null;

        chmod($target, $this->filePermissions);

        return true;
    }

    
    protected function createImage(string $path = '', string $imageType = '')
    {
        if ($this->resource !== null) {
            return $this->resource;
        }

        if ($path === '') {
            $path = $this->image()->getPathname();
        }

        if ($imageType === '') {
            $imageType = $this->image()->imageType;
        }

        return $this->getImageResource($path, $imageType);
    }

    
    protected function ensureResource()
    {
        if ($this->resource === null) {
            
            $this->resource = $this->getImageResource(
                $this->image()->getPathname(),
                $this->image()->imageType,
            );
        }
    }

    
    protected function getImageResource(string $path, int $imageType)
    {
        switch ($imageType) {
            case IMAGETYPE_GIF:
                if (! function_exists('imagecreatefromgif')) {
                    throw ImageException::forInvalidImageCreate(lang('Images.gifNotSupported'));
                }

                return imagecreatefromgif($path);

            case IMAGETYPE_JPEG:
                if (! function_exists('imagecreatefromjpeg')) {
                    throw ImageException::forInvalidImageCreate(lang('Images.jpgNotSupported'));
                }

                return imagecreatefromjpeg($path);

            case IMAGETYPE_PNG:
                if (! function_exists('imagecreatefrompng')) {
                    throw ImageException::forInvalidImageCreate(lang('Images.pngNotSupported'));
                }

                return @imagecreatefrompng($path);

            case IMAGETYPE_WEBP:
                if (! function_exists('imagecreatefromwebp')) {
                    throw ImageException::forInvalidImageCreate(lang('Images.webpNotSupported'));
                }

                return imagecreatefromwebp($path);

            default:
                throw ImageException::forInvalidImageCreate('Ima');
        }
    }

    
    protected function _text(string $text, array $options = [])
    {
        
        
        
        
        
        

        if ($options['vAlign'] === 'bottom') {
            $options['vOffset'] *= -1;
        }

        if ($options['hAlign'] === 'right') {
            $options['hOffset'] *= -1;
        }

        
        
        
        if (! empty($options['fontPath'])) {
            if (function_exists('imagettfbbox')) {
                $temp = imagettfbbox($options['fontSize'], 0, $options['fontPath'], $text);
                $temp = $temp[2] - $temp[0];

                $fontwidth = $temp / strlen($text);
            } else {
                $fontwidth = $options['fontSize'] - ($options['fontSize'] / 4);
            }

            $fontheight = $options['fontSize'];
        } else {
            $fontwidth  = imagefontwidth($options['fontSize']);
            $fontheight = imagefontheight($options['fontSize']);
        }

        $options['fontheight'] = $fontheight;
        $options['fontwidth']  = $fontwidth;

        
        $xAxis = $options['hOffset'] + $options['padding'];
        $yAxis = $options['vOffset'] + $options['padding'];

        
        if ($options['vAlign'] === 'middle') {
            
            $yAxis += ($this->image()->origHeight / 2) + ($fontheight / 2) - $options['padding'] - $fontheight - $options['shadowOffset'];
        } elseif ($options['vAlign'] === 'bottom') {
            $yAxis = ($this->image()->origHeight - $fontheight - $options['shadowOffset'] - ($fontheight / 2)) - $yAxis;
        }

        
        if ($options['hAlign'] === 'right') {
            $xAxis += ($this->image()->origWidth - ($fontwidth * strlen($text)) - $options['shadowOffset']) - (2 * $options['padding']);
        } elseif ($options['hAlign'] === 'center') {
            $xAxis += floor(($this->image()->origWidth - ($fontwidth * strlen($text))) / 2);
        }

        $options['xAxis'] = $xAxis;
        $options['yAxis'] = $yAxis;

        if ($options['withShadow']) {
            
            $options['xShadow'] = $xAxis + $options['shadowOffset'];
            $options['yShadow'] = $yAxis + $options['shadowOffset'];

            $this->textOverlay($text, $options, true);
        }

        $this->textOverlay($text, $options);
    }

    
    protected function textOverlay(string $text, array $options = [], bool $isShadow = false)
    {
        $src = $this->createImage();

        
        $opacity = (int) ($options['opacity'] * 127);

        
        imagealphablending($src, true);

        $color = $isShadow ? $options['shadowColor'] : $options['color'];

        
        if (strlen($color) === 3) {
            $color = implode('', array_map(str_repeat(...), str_split($color), [2, 2, 2]));
        }

        $color = str_split(substr($color, 0, 6), 2);
        $color = imagecolorclosestalpha($src, hexdec($color[0]), hexdec($color[1]), hexdec($color[2]), $opacity);

        $xAxis = $isShadow ? $options['xShadow'] : $options['xAxis'];
        $yAxis = $isShadow ? $options['yShadow'] : $options['yAxis'];

        
        if (! empty($options['fontPath'])) {
            
            imagettftext($src, $options['fontSize'], 0, (int) $xAxis, (int) ($yAxis + $options['fontheight']), $color, $options['fontPath'], $text);
        } else {
            imagestring($src, (int) $options['fontSize'], (int) $xAxis, (int) $yAxis, $text, $color);
        }

        $this->resource = $src;
    }

    
    public function _getWidth()
    {
        return imagesx($this->resource);
    }

    
    public function _getHeight()
    {
        return imagesy($this->resource);
    }
}
