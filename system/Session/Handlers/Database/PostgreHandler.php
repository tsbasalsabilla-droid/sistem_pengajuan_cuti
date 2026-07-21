<?php

declare(strict_types=1);



namespace CodeIgniter\Session\Handlers\Database;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Session\Handlers\DatabaseHandler;


class PostgreHandler extends DatabaseHandler
{
    
    protected function setSelect(BaseBuilder $builder)
    {
        $builder->select("encode(data, 'base64') AS data");
    }

    
    protected function decodeData($data)
    {
        return base64_decode(rtrim($data), true);
    }

    
    protected function prepareData(string $data): string
    {
        return '\x' . bin2hex($data);
    }

    
    public function gc($max_lifetime): false|int
    {
        $separator = '\'';
        $interval  = implode($separator, ['', "{$max_lifetime} second", '']);

        return $this->db->table($this->table)->where('timestamp <', "now() - INTERVAL {$interval}", false)->delete() ? 1 : $this->fail();
    }

    
    protected function lockSession(string $sessionID): bool
    {
        $arg = "hashtext('{$sessionID}')" . ($this->matchIP ? ", hashtext('{$this->ipAddress}')" : '');
        if ($this->db->simpleQuery("SELECT pg_advisory_lock({$arg})") !== false) {
            $this->lock = $arg;

            return true;
        }

        return $this->fail();
    }

    
    protected function releaseLock(): bool
    {
        if (! $this->lock) {
            return true;
        }

        if ($this->db->simpleQuery("SELECT pg_advisory_unlock({$this->lock})") !== false) {
            $this->lock = false;

            return true;
        }

        return $this->fail();
    }
}
