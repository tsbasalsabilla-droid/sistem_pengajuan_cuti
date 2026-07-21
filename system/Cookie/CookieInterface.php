<?php

declare(strict_types=1);



namespace CodeIgniter\Cookie;


interface CookieInterface
{
    
    public const SAMESITE_NONE = 'None';

    
    public const SAMESITE_LAX = 'Lax';

    
    public const SAMESITE_STRICT = 'Strict';

    
    public const ALLOWED_SAMESITE_VALUES = [
        self::SAMESITE_NONE,
        self::SAMESITE_LAX,
        self::SAMESITE_STRICT,
    ];

    
    public const EXPIRES_FORMAT = 'D, d M Y H:i:s T';

    
    public function getId(): string;

    
    public function getPrefix(): string;

    
    public function getName(): string;

    
    public function getPrefixedName(): string;

    
    public function getValue(): string;

    
    public function getExpiresTimestamp(): int;

    
    public function getExpiresString(): string;

    
    public function isExpired(): bool;

    
    public function getMaxAge(): int;

    
    public function getPath(): string;

    
    public function getDomain(): string;

    
    public function isSecure(): bool;

    
    public function isHTTPOnly(): bool;

    
    public function getSameSite(): string;

    
    public function isRaw(): bool;

    
    public function getOptions(): array;

    
    public function toHeaderString(): string;

    
    public function __toString();

    
    public function toArray(): array;
}
