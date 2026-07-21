<?php

declare(strict_types=1);



namespace Kint\Value;

use Kint\Utils;
use Kint\Value\Context\ContextInterface;
use Kint\Value\Representation\ContainerRepresentation;

class StreamValue extends ResourceValue
{
    
    protected array $stream_meta;
    
    protected ?string $uri;

    
    public function __construct(ContextInterface $context, array $stream_meta, ?string $uri)
    {
        parent::__construct($context, 'stream');
        $this->stream_meta = $stream_meta;
        $this->uri = $uri;

        if ($stream_meta) {
            $this->addRepresentation(new ContainerRepresentation('Stream', $stream_meta, null, true));
        }
    }

    public function getHint(): string
    {
        return parent::getHint() ?? 'stream';
    }

    public function getDisplayValue(): ?string
    {
        if (null === $this->uri) {
            return null;
        }

        if ('/' === $this->uri[0] && \stream_is_local($this->uri)) {
            return Utils::shortenPath($this->uri);
        }

        return $this->uri;
    }

    public function getDisplayChildren(): array
    {
        return $this->stream_meta;
    }
}
