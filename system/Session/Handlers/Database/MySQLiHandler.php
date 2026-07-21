<?php

declare(strict_types=1);



namespace CodeIgniter\Session\Handlers\Database;

use CodeIgniter\Session\Handlers\DatabaseHandler;


class MySQLiHandler extends DatabaseHandler
{
    
    protected function lockSession(string $sessionID): bool
    {
        $arg = md5($sessionID . ($this->matchIP ? '_' . $this->ipAddress : ''));
        if ($this->db->query("SELECT GET_LOCK('{$arg}', 300) AS ci_session_lock")->getRow()->ci_session_lock) {
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

        if ($this->db->query("SELECT RELEASE_LOCK('{$this->lock}') AS ci_session_lock")->getRow()->ci_session_lock) {
            $this->lock = false;

            return true;
        }

        return $this->fail();
    }
}
