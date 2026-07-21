<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Mock;

use CodeIgniter\Security\Security;

class MockSecurity extends Security
{
    protected function doSendCookie(): void
    {
        $_COOKIE['csrf_cookie_name'] = $this->hash;
    }

    protected function randomize(string $hash): string
    {
        $keyBinary  = hex2bin('005513c290126d34d41bf41c5265e0f1');
        $hashBinary = hex2bin($hash);

        return bin2hex(($hashBinary ^ $keyBinary) . $keyBinary);
    }
}
