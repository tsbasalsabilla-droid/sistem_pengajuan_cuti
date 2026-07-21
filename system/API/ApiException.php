<?php

declare(strict_types=1);



namespace CodeIgniter\API;

use CodeIgniter\Exceptions\FrameworkException;


final class ApiException extends FrameworkException
{
    
    public static function forInvalidFields(string $field): self
    {
        return new self(lang('Api.invalidFields', [$field]));
    }

    
    public static function forInvalidIncludes(string $include): self
    {
        return new self(lang('Api.invalidIncludes', [$include]));
    }

    
    public static function forMissingInclude(string $include): self
    {
        return new self(lang('Api.missingInclude', [$include]));
    }

    
    public static function forTransformerNotFound(string $transformerClass): self
    {
        return new self(lang('Api.transformerNotFound', [$transformerClass]));
    }

    
    public static function forInvalidTransformer(string $transformerClass): self
    {
        return new self(lang('Api.invalidTransformer', [$transformerClass]));
    }
}
