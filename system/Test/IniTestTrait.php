<?php

declare(strict_types=1);



namespace CodeIgniter\Test;

trait IniTestTrait
{
    private array $iniSettings = [];

    private function backupIniValues(array $keys): void
    {
        foreach ($keys as $key) {
            $this->iniSettings[$key] = ini_get($key);
        }
    }

    private function restoreIniValues(): void
    {
        foreach ($this->iniSettings as $key => $value) {
            ini_set($key, $value);
        }

        $this->iniSettings = [];
    }
}
