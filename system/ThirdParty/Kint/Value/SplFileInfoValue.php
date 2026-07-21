<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Utils;
use Kint\Value\Context\ContextInterface;
use RuntimeException;
use SplFileInfo;

class SplFileInfoValue extends InstanceValue
{
    
    protected string $path;
    
    protected ?int $filesize = null;

    public function __construct(ContextInterface $context, SplFileInfo $info)
    {
        parent::__construct($context, \get_class($info), \spl_object_hash($info), \spl_object_id($info));

        $this->path = $info->getPathname();

        try {
            
            if ('' !== $this->path && $info->getRealPath()) {
                $this->filesize = $info->getSize();
            }
        } catch (RuntimeException $e) {
            if (false === \strpos($e->getMessage(), ' open_basedir ')) {
                throw $e; 
            }
        }
    }

    public function getHint(): string
    {
        return parent::getHint() ?? 'splfileinfo';
    }

    
    public function getFileSize(): ?int
    {
        return $this->filesize;
    }

    public function getDisplaySize(): ?string
    {
        if (null === $this->filesize) {
            return null;
        }

        $size = Utils::getHumanReadableBytes($this->filesize);

        return $size['value'].$size['unit'];
    }

    public function getDisplayValue(): ?string
    {
        $shortpath = Utils::shortenPath($this->path);

        if ($shortpath !== $this->path) {
            return $shortpath;
        }

        return parent::getDisplayValue();
    }
}
