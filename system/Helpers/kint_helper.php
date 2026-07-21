<?php

declare(strict_types=1);





if (! function_exists('dd')) {
    if (class_exists(Kint::class)) {
        
        function dd(...$vars): void
        {
            
            Kint::$aliases[] = 'dd';
            Kint::dump(...$vars);

            exit;
            
        }
    } else {
        
        
        function dd(...$vars)
        {
            return 0;
        }
    }
}

if (! function_exists('d') && ! class_exists(Kint::class)) {
    
    
    function d(...$vars)
    {
        return 0;
    }
}

if (! function_exists('trace')) {
    if (class_exists(Kint::class)) {
        
        
        function trace(): void
        {
            Kint::$aliases[] = 'trace';
            Kint::trace();
        }
    } else {
        
        
        function trace()
        {
            return 0;
        }
    }
}
