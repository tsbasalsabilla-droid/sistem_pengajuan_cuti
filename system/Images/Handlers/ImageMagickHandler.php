<?php

declare(strict_types=1);



namespace CodeIgniter\Images\Handlers;

use CodeIgniter\Images\Exceptions\ImageException;
use Config\Images;
use Imagick;
use ImagickDraw;
use ImagickDrawException;
use ImagickException;
use ImagickPixel;
use ImagickPixelException;


class ImageMagickHandler extends BaseHandler
{
    
    protected $resource;

    
    public function __construct($config = null)
    {
        parent::__construct($config);

        if (! extension_loaded('imagick')) {
            throw ImageException::forMissingExtension('IMAGICK');  
        }
    }

    
    protected function ensureResource()
    {
        if (! $this->resource instanceof Imagick) {
            
            $this->image();

            try {
                $this->resource = new Imagick();
                $this->resource->readImage($this->image()->getPathname());

                
                if ($this->resource->getImageWidth() === 0 || $this->resource->getImageHeight() === 0) {
                    throw ImageException::forInvalidImageCreate($this->image()->getPathname());
                }

                $this->supportedFormatCheck();
            } catch (ImagickException $e) {
                throw ImageException::forInvalidImageCreate($e->getMessage());
            }
        }
    }

    
    protected function process(string $action, int $quality = 100)
    {
        $this->image();

        $this->ensureResource();

        try {
            switch ($action) {
                case 'resize':
                    $this->resource->resizeImage(
                        $this->width,
                        $this->height,
                        Imagick::FILTER_LANCZOS,
                        0,
                    );
                    break;

                case 'crop':
                    $width  = $this->width;
                    $height = $this->height;
                    $xAxis  = $this->xAxis ?? 0;
                    $yAxis  = $this->yAxis ?? 0;

                    $this->resource->cropImage(
                        $width,
                        $height,
                        $xAxis,
                        $yAxis,
                    );

                    
                    $this->resource->setImagePage(0, 0, 0, 0);
                    break;
            }

            
            if (in_array($this->image()->imageType, $this->supportTransparency, true)
                && $this->resource->getImageAlphaChannel() === Imagick::ALPHACHANNEL_UNDEFINED) {
                $this->resource->setImageAlphaChannel(Imagick::ALPHACHANNEL_OPAQUE);
            }
        } catch (ImagickException) {
            throw ImageException::forImageProcessFailed();
        }

        return $this;
    }

    
    public function _resize(bool $maintainRatio = false)
    {
        if ($maintainRatio) {
            
            $this->ensureResource();

            
            $this->resource->thumbnailImage($this->width, $this->height, true);

            return $this;
        }

        
        return $this->process('resize');
    }

    
    public function _crop()
    {
        
        $result = $this->process('crop');

        
        if ($this->resource instanceof Imagick) {
            $imgWidth  = $this->resource->getImageWidth();
            $imgHeight = $this->resource->getImageHeight();

            if ($this->xAxis >= $imgWidth || $this->yAxis >= $imgHeight) {
                
                $background = new Imagick();
                $background->newImage($this->width, $this->height, new ImagickPixel('transparent'));
                $background->setImageFormat($this->resource->getImageFormat());

                
                $background->compositeImage($this->resource, Imagick::COMPOSITE_OVER, 0, 0);

                
                $this->resource = $background;
            }
        }

        return $result;
    }

    
    protected function _rotate(int $angle)
    {
        $this->ensureResource();

        
        $this->resource->setImageBackgroundColor(new ImagickPixel('transparent'));
        $this->resource->rotateImage(new ImagickPixel('transparent'), $angle);

        
        $this->resource->setImagePage($this->resource->getImageWidth(), $this->resource->getImageHeight(), 0, 0);

        return $this;
    }

    
    protected function _flatten(int $red = 255, int $green = 255, int $blue = 255)
    {
        $this->ensureResource();

        
        $bg = new ImagickPixel("rgb({$red},{$green},{$blue})");

        
        $canvas = new Imagick();
        $canvas->newImage(
            $this->resource->getImageWidth(),
            $this->resource->getImageHeight(),
            $bg,
            $this->resource->getImageFormat(),
        );

        
        $canvas->compositeImage(
            $this->resource,
            Imagick::COMPOSITE_OVER,
            0,
            0,
        );

        
        $this->resource->clear();
        $this->resource = $canvas;

        return $this;
    }

    
    protected function _flip(string $direction)
    {
        $this->ensureResource();

        if ($direction === 'horizontal') {
            $this->resource->flopImage();
        } else {
            $this->resource->flipImage();
        }

        return $this;
    }

    
    public function getVersion()
    {
        $version = Imagick::getVersion();

        if (preg_match('/ImageMagick\s+(\d+\.\d+\.\d+)/', $version['versionString'], $matches)) {
            return $matches[1];
        }

        return '';
    }

    
    protected function supportedFormatCheck()
    {
        if (! $this->resource instanceof Imagick) {
            return;
        }

        if ($this->image()->imageType === IMAGETYPE_WEBP && ! in_array('WEBP', Imagick::queryFormats(), true)) {
            throw ImageException::forInvalidImageCreate(lang('images.webpNotSupported'));
        }
    }

    
    public function save(?string $target = null, int $quality = 90): bool
    {
        $original = $target;
        $target   = ($target === null || $target === '') ? $this->image()->getPathname() : $target;

        
        
        if (! $this->resource instanceof Imagick && $quality === 100) {
            if ($original === null) {
                return true;
            }

            $name = basename($target);
            $path = pathinfo($target, PATHINFO_DIRNAME);

            return $this->image()->copy($path, $name);
        }

        $this->ensureResource();

        $this->resource->setImageCompressionQuality($quality);

        if ($target !== null) {
            $extension = pathinfo($target, PATHINFO_EXTENSION);
            $this->resource->setImageFormat($extension);
        }

        try {
            $result = $this->resource->writeImage($target);

            chmod($target, $this->filePermissions);

            $this->resource->clear();
            $this->resource = null;

            return $result;
        } catch (ImagickException) {
            throw ImageException::forSaveFailed();
        }
    }

    
    protected function _text(string $text, array $options = [])
    {
        $this->ensureResource();

        $draw = new ImagickDraw();

        if (isset($options['fontPath'])) {
            $draw->setFont($options['fontPath']);
        }

        if (isset($options['fontSize'])) {
            $draw->setFontSize($options['fontSize']);
        }

        if (isset($options['color'])) {
            $color = $options['color'];

            
            if (strlen($color) === 3) {
                $color = implode('', array_map(str_repeat(...), str_split($color), [2, 2, 2]));
            }

            [$r, $g, $b] = sscanf("#{$color}", '#%02x%02x%02x');
            $opacity     = $options['opacity'] ?? 1.0;
            $draw->setFillColor(new ImagickPixel("rgba({$r},{$g},{$b},{$opacity})"));
        }

        
        $imgWidth  = $this->resource->getImageWidth();
        $imgHeight = $this->resource->getImageHeight();
        $xAxis     = 0;
        $yAxis     = 0;

        
        $padding = $options['padding'] ?? 0;

        if (isset($options['hAlign'])) {
            $hOffset = $options['hOffset'] ?? 0;

            switch ($options['hAlign']) {
                case 'left':
                    $xAxis = $hOffset + $padding;
                    $draw->setTextAlignment(Imagick::ALIGN_LEFT);
                    break;

                case 'center':
                    $xAxis = $imgWidth / 2 + $hOffset;
                    $draw->setTextAlignment(Imagick::ALIGN_CENTER);
                    break;

                case 'right':
                    $xAxis = $imgWidth - $hOffset - $padding;
                    $draw->setTextAlignment(Imagick::ALIGN_RIGHT);
                    break;
            }
        }

        if (isset($options['vAlign'])) {
            $vOffset = $options['vOffset'] ?? 0;

            switch ($options['vAlign']) {
                case 'top':
                    $yAxis = $vOffset + $padding + ($options['fontSize'] ?? 16);
                    break;

                case 'middle':
                    $yAxis = $imgHeight / 2 + $vOffset;
                    break;

                case 'bottom':
                    
                    $yAxis = $vOffset < 0 ? $imgHeight + $vOffset - $padding : $imgHeight - $vOffset - $padding;
                    break;
            }
        }

        if (isset($options['withShadow'])) {
            $shadow = clone $draw;

            if (isset($options['shadowColor'])) {
                $shadowColor = $options['shadowColor'];

                
                if (strlen($shadowColor) === 3) {
                    $shadowColor = implode('', array_map(str_repeat(...), str_split($shadowColor), [2, 2, 2]));
                }

                [$sr, $sg, $sb] = sscanf("#{$shadowColor}", '#%02x%02x%02x');
                $shadow->setFillColor(new ImagickPixel("rgb({$sr},{$sg},{$sb})"));
            } else {
                $shadow->setFillColor(new ImagickPixel('rgba(0,0,0,0.5)'));
            }

            $offset = $options['shadowOffset'] ?? 3;

            $this->resource->annotateImage(
                $shadow,
                $xAxis + $offset,
                $yAxis + $offset,
                0,
                $text,
            );
        }

        
        $this->resource->annotateImage(
            $draw,
            $xAxis,
            $yAxis,
            0,
            $text,
        );
    }

    
    public function _getWidth()
    {
        $this->ensureResource();

        return $this->resource->getImageWidth();
    }

    
    public function _getHeight()
    {
        $this->ensureResource();

        return $this->resource->getImageHeight();
    }

    
    public function reorient(bool $silent = false)
    {
        $orientation = $this->getEXIF('Orientation', $silent);

        return match ($orientation) {
            2       => $this->flip('horizontal'),
            3       => $this->rotate(180),
            4       => $this->rotate(180)->flip('horizontal'),
            5       => $this->rotate(90)->flip('horizontal'),
            6       => $this->rotate(90),
            7       => $this->rotate(270)->flip('horizontal'),
            8       => $this->rotate(270),
            default => $this,
        };
    }

    
    public function clearMetadata(): static
    {
        $this->ensureResource();

        $this->resource->stripImage();

        return $this;
    }
}
