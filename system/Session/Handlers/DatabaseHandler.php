<?php

declare(strict_types=1);



namespace CodeIgniter\Session\Handlers;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Session\Exceptions\SessionException;
use Config\Database;
use Config\Session as SessionConfig;


class DatabaseHandler extends BaseHandler
{
    
    protected $DBGroup;

    
    protected $table;

    
    protected $db;

    
    protected $platform;

    
    protected $rowExists = false;

    
    protected string $idPrefix;

    
    public function __construct(SessionConfig $config, string $ipAddress)
    {
        parent::__construct($config, $ipAddress);

        $this->table = $this->savePath;

        if ($this->table === '') {
            throw SessionException::forMissingDatabaseTable();
        }

        
        $this->DBGroup = $config->DBGroup ?? config(Database::class)->defaultGroup;
        
        $this->idPrefix = $config->cookieName . ':';
        $this->db       = Database::connect($this->DBGroup);
        $this->platform = $this->db->getPlatform();
    }

    
    public function open($path, $name): bool
    {
        if ($this->db->connID === false) {
            $this->db->initialize();
        }

        return true;
    }

    
    public function read($id): false|string
    {
        if ($this->lockSession($id) === false) {
            $this->fingerprint = md5('');

            return '';
        }

        if (! isset($this->sessionID)) {
            $this->sessionID = $id;
        }

        $builder = $this->db->table($this->table)->where('id', $this->idPrefix . $id);

        if ($this->matchIP) {
            $builder = $builder->where('ip_address', $this->ipAddress);
        }

        $this->setSelect($builder);

        $result = $builder->get()->getRow();

        if ($result === null) {
            
            
            
            $this->rowExists   = false;
            $this->fingerprint = md5('');

            return '';
        }

        $result = is_bool($result) ? '' : $this->decodeData($result->data);

        $this->fingerprint = md5($result);
        $this->rowExists   = true;

        return $result;
    }

    
    protected function setSelect(BaseBuilder $builder)
    {
        $builder->select('data');
    }

    
    protected function decodeData($data)
    {
        return $data;
    }

    
    public function write($id, $data): bool
    {
        if ($this->lock === false) {
            return $this->fail();
        }

        if ($this->sessionID !== $id) {
            $this->rowExists = false;
            $this->sessionID = $id;
        }

        if ($this->rowExists === false) {
            $insertData = [
                'id'         => $this->idPrefix . $id,
                'ip_address' => $this->ipAddress,
                'data'       => $this->prepareData($data),
            ];

            if (! $this->db->table($this->table)->set('timestamp', 'now()', false)->insert($insertData)) {
                return $this->fail();
            }

            $this->fingerprint = md5($data);
            $this->rowExists   = true;

            return true;
        }

        $builder = $this->db->table($this->table)->where('id', $this->idPrefix . $id);

        if ($this->matchIP) {
            $builder = $builder->where('ip_address', $this->ipAddress);
        }

        $updateData = [];

        if ($this->fingerprint !== md5($data)) {
            $updateData['data'] = $this->prepareData($data);
        }

        if (! $builder->set('timestamp', 'now()', false)->update($updateData)) {
            return $this->fail();
        }

        $this->fingerprint = md5($data);

        return true;
    }

    
    protected function prepareData(string $data): string
    {
        return $data;
    }

    
    public function close(): bool
    {
        return ($this->lock && ! $this->releaseLock()) ? $this->fail() : true;
    }

    
    public function destroy($id): bool
    {
        if ($this->lock) {
            $builder = $this->db->table($this->table)->where('id', $this->idPrefix . $id);

            if ($this->matchIP) {
                $builder = $builder->where('ip_address', $this->ipAddress);
            }

            if (! $builder->delete()) {
                return $this->fail();
            }
        }

        if ($this->close()) {
            $this->destroyCookie();

            return true;
        }

        return $this->fail();
    }

    
    public function gc($max_lifetime): false|int
    {
        return $this->db->table($this->table)->where(
            'timestamp <',
            "now() - INTERVAL {$max_lifetime} second",
            false,
        )->delete() ? 1 : $this->fail();
    }

    
    protected function releaseLock(): bool
    {
        if (! $this->lock) {
            return true;
        }

        
        return parent::releaseLock();
    }
}
