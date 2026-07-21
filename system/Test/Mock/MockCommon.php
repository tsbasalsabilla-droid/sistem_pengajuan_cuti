<?php

declare(strict_types=1);



if (! function_exists('is_cli')) {
    
    function is_cli(?bool $newReturn = null): bool
    {
        
        static $returnValue = true;

        if ($newReturn !== null) {
            $returnValue = $newReturn;
        }

        return $returnValue;
    }
}
