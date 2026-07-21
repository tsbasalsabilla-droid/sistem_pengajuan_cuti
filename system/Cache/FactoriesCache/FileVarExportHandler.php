<?php

declare(strict_types=1);



namespace CodeIgniter\Cache\FactoriesCache;

final class FileVarExportHandler
{
    private string $path = WRITEPATH . 'cache';

    public function save(string $key, mixed $val): void
    {
        $val = var_export($val, true);

        
        $tmp = $this->path . "/{$key}." . uniqid('', true) . '.tmp';
        file_put_contents($tmp, '<?php return ' . $val . ';', LOCK_EX);

        rename($tmp, $this->path . "/{$key}");
    }

    public function delete(string $key): void
    {
        @unlink($this->path . "/{$key}");
    }

    public function get(string $key): mixed
    {
        return @include $this->path . "/{$key}";
    }
}
