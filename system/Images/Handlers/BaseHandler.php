<?php

declare(strict_types=1);



namespace CodeIgniter\Images\Handlers;

use CodeIgniter\Exceptions\InvalidArgumentException;
use CodeIgniter\Images\Exceptions\ImageException;
use CodeIgniter\Images\Image;
use CodeIgniter\Images\ImageHandlerInterface;
use Config\Images;


abstract class BaseHandler implements ImageHandlerInterface
{
    
    protected $config;

    
    protected $image;

    
    protected $verified = false;

    
    protected $width = 0;

    
    protected $height = 0;

    
    protected $filePermissions = 0644;

    
    protected $xAxis = 0;

    
    protected $yAxis = 0;

    
    protected $masterDim = 'auto';

    
    protected $textDefaults = [
        'fontPath'     => null,
        'fontSize'     => 16,
        'color'        => 'ffffff',
        'opacity'      => 1.0,
        'vAlign'       => 'bottom',
        'hAlign'       => 'center',
        'vOffset'      => 0,
        'hOffset'      => 0,
        'padding'      => 0,
        'withShadow'   => false,
        'shadowColor'  => '000000',
        'shadowOffset' => 3,
    ];

    
    protected $supportTransparency = [
        IMAGETYPE_PNG,
        IMAGETYPE_WEBP,
    ];

    
    protected $resource;

    
    public function __construct($config = null)
    {
        $this->config = $config ?? new Images();
    }

    
    public function withFile(string $path)
    {
        
        
        $this->resource = null;
        $this->verified = false;

        $this->image = new Image($path, true);

        $this->image->getProperties(false);
        $this->width  = $this->image->origWidth;
        $this->height = $this->image->origHeight;

        return $this;
    }

    
    abstract protected function ensureResource();

    
    public function getFile()
    {
        return $this->image;
    }

    
    protected function image(): Image
    {
        if ($this->verified) {
            return $this->image;
        }

        
        if ($this->image === null) {
            throw ImageException::forMissingImage();
        }

        
        if (! $this->image instanceof Image) {
            throw ImageException::forInvalidPath();
        }

        
        if (! is_int($this->image->imageType)) {
            throw ImageException::forFileNotSupported();
        }

        
        $this->verified = true;

        return $this->image;
    }

    
    public function getResource()
    {
        $this->ensureResource();

        return $this->resource;
    }

    
    public function withResource()
    {
        $this->ensureResource();

        return $this;
    }

    
    public function resize(int $width, int $height, bool $maintainRatio = false, string $masterDim = 'auto')
    {
        
        if ($this->image()->origWidth === $width && $this->image()->origHeight === $height) {
            return $this;
        }

        $this->width  = $width;
        $this->height = $height;

        if ($maintainRatio) {
            $this->masterDim = $masterDim;
            $this->reproportion();
        }

        return $this->_resize($maintainRatio);
    }

    
    public function crop(?int $width = null, ?int $height = null, ?int $x = null, ?int $y = null, bool $maintainRatio = false, string $masterDim = 'auto')
    {
        $this->width  = $width;
        $this->height = $height;
        $this->xAxis  = $x;
        $this->yAxis  = $y;

        if ($maintainRatio) {
            $this->masterDim = $masterDim;
            $this->reproportion();
        }

        $result = $this->_crop();

        $this->xAxis = null;
        $this->yAxis = null;

        return $result;
    }

    
    public function convert(int $imageType)
    {
        $this->ensureResource();

        $this->image()->imageType = $imageType;

        return $this;
    }

    
    public function rotate(float $angle)
    {
        
        $degs = [
            90.0,
            180.0,
            270.0,
        ];

        if (! in_array($angle, $degs, true)) {
            throw ImageException::forMissingAngle();
        }

        
        $angle = (int) $angle;

        
        if ($angle === 90 || $angle === 270) {
            $temp         = $this->height;
            $this->width  = $this->height;
            $this->height = $temp;
        }

        
        $this->_rotate($angle);

        return $this;
    }

    
    public function flatten(int $red = 255, int $green = 255, int $blue = 255)
    {
        $this->width  = $this->image()->origWidth;
        $this->height = $this->image()->origHeight;

        return $this->_flatten($red, $green, $blue);
    }

    
    abstract protected function _flatten(int $red = 255, int $green = 255, int $blue = 255);

    
    abstract protected function _rotate(int $angle);

    
    public function flip(string $dir = 'vertical')
    {
        $dir = strtolower($dir);

        if ($dir !== 'vertical' && $dir !== 'horizontal') {
            throw ImageException::forInvalidDirection($dir);
        }

        return $this->_flip($dir);
    }

    
    abstract protected function _flip(string $direction);

    public function text(string $text, array $options = [])
    {
        $options                = array_merge($this->textDefaults, $options);
        $options['color']       = trim($options['color'], '# ');
        $options['shadowColor'] = trim($options['shadowColor'], '# ');

        $this->_text($text, $options);

        return $this;
    }

    
    abstract protected function _text(string $text, array $options = []);

    
    abstract public function _resize(bool $maintainRatio = false);

    
    abstract public function _crop();

    
    abstract public function _getWidth();

    
    abstract public function _getHeight();

    
    public function reorient(bool $silent = false)
    {
        $orientation = $this->getEXIF('Orientation', $silent);

        return match ($orientation) {
            2       => $this->flip('horizontal'),
            3       => $this->rotate(180),
            4       => $this->rotate(180)->flip('horizontal'),
            5       => $this->rotate(270)->flip('horizontal'),
            6       => $this->rotate(270),
            7       => $this->rotate(90)->flip('horizontal'),
            8       => $this->rotate(90),
            default => $this,
        };
    }

    
    public function getEXIF(?string $key = null, bool $silent = false)
    {
        if (! function_exists('exif_read_data')) {
            if ($silent) {
                return null;
            }

            throw ImageException::forEXIFUnsupported(); 
        }

        $exif = null; 

        switch ($this->image()->imageType) {
            case IMAGETYPE_JPEG:
            case IMAGETYPE_TIFF_II:
                $exif = @exif_read_data($this->image()->getPathname());
                if ($key !== null && is_array($exif)) {
                    $exif = $exif[$key] ?? false;
                }
        }

        return $exif;
    }

    
    public function fit(int $width, ?int $height = null, string $position = 'center')
    {
        $origWidth  = $this->image()->origWidth;
        $origHeight = $this->image()->origHeight;

        [$cropWidth, $cropHeight] = $this->calcAspectRatio($width, $height, $origWidth, $origHeight);

        if ($height === null) {
            $height = (int) ceil(($width / $cropWidth) * $cropHeight);
        }

        [$x, $y] = $this->calcCropCoords($cropWidth, $cropHeight, $origWidth, $origHeight, $position);

        return $this->crop($cropWidth, $cropHeight, (int) $x, (int) $y)->resize($width, $height);
    }

    
    protected function calcAspectRatio($width, $height = null, $origWidth = 0, $origHeight = 0): array
    {
        if (empty($origWidth) || empty($origHeight)) {
            throw new InvalidArgumentException('You must supply the parameters: origWidth, origHeight.');
        }

        
        
        if ($height === null) {
            $height = ($width / $origWidth) * $origHeight;

            return [
                $width,
                (int) $height,
            ];
        }

        $xRatio = $width / $origWidth;
        $yRatio = $height / $origHeight;

        if ($xRatio > $yRatio) {
            return [
                $origWidth,
                (int) ($origWidth * $height / $width),
            ];
        }

        return [
            (int) ($origHeight * $width / $height),
            $origHeight,
        ];
    }

    
    protected function calcCropCoords($width, $height, $origWidth, $origHeight, $position): array
    {
        $position = strtolower($position);

        $x = $y = 0;

        switch ($position) {
            case 'top-left':
                $x = 0;
                $y = 0;
                break;

            case 'top':
                $x = floor(($origWidth - $width) / 2);
                $y = 0;
                break;

            case 'top-right':
                $x = $origWidth - $width;
                $y = 0;
                break;

            case 'left':
                $x = 0;
                $y = floor(($origHeight - $height) / 2);
                break;

            case 'center':
                $x = floor(($origWidth - $width) / 2);
                $y = floor(($origHeight - $height) / 2);
                break;

            case 'right':
                $x = ($origWidth - $width);
                $y = floor(($origHeight - $height) / 2);
                break;

            case 'bottom-left':
                $x = 0;
                $y = $origHeight - $height;
                break;

            case 'bottom':
                $x = floor(($origWidth - $width) / 2);
                $y = $origHeight - $height;
                break;

            case 'bottom-right':
                $x = ($origWidth - $width);
                $y = $origHeight - $height;
                break;
        }

        return [
            $x,
            $y,
        ];
    }

    
    abstract public function getVersion();

    
    abstract public function save(?string $target = null, int $quality = 90);

    
    abstract protected function process(string $action);

    
    public function __call(string $name, array $args = [])
    {
        if (method_exists($this->image(), $name)) {
            return $this->image()->{$name}(...$args);
        }

        return null;
    }

    
    protected function reproportion()
    {
        if (($this->width === 0 && $this->height === 0) || $this->image()->origWidth === 0 || $this->image()->origHeight === 0 || (! ctype_digit((string) $this->width) && ! ctype_digit((string) $this->height)) || ! ctype_digit((string) $this->image()->origWidth) || ! ctype_digit((string) $this->image()->origHeight)) {
            return;
        }

        
        $this->width  = (int) $this->width;
        $this->height = (int) $this->height;

        if ($this->masterDim !== 'width' && $this->masterDim !== 'height') {
            if ($this->width > 0 && $this->height > 0) {
                $this->masterDim = ((($this->image()->origHeight / $this->image()->origWidth) - ($this->height / $this->width)) < 0) ? 'width' : 'height';
            } else {
                $this->masterDim = ($this->height === 0) ? 'width' : 'height';
            }
        } elseif (($this->masterDim === 'width' && $this->width === 0) || ($this->masterDim === 'height' && $this->height === 0)
        ) {
            return;
        }

        if ($this->masterDim === 'width') {
            $this->height = (int) ceil($this->width * $this->image()->origHeight / $this->image()->origWidth);
        } else {
            $this->width = (int) ceil($this->image()->origWidth * $this->height / $this->image()->origHeight);
        }
    }

    
    public function getWidth()
    {
        return ($this->resource !== null) ? $this->_getWidth() : $this->width;
    }

    
    public function getHeight()
    {
        return ($this->resource !== null) ? $this->_getHeight() : $this->height;
    }

    
    public function clearMetadata(): static
    {
        return $this;
    }
}
