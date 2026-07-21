<?php

declare(strict_types=1);



namespace CodeIgniter\Test\Constraints;

use CodeIgniter\Database\ConnectionInterface;
use PHPUnit\Framework\Constraint\Constraint;

class SeeInDatabase extends Constraint
{
    
    protected $show = 3;

    
    protected $db;

    
    protected $data;

    
    public function __construct(ConnectionInterface $db, array $data)
    {
        $this->db   = $db;
        $this->data = $data;
    }

    
    protected function matches($table): bool
    {
        return $this->db->table($table)->where($this->data)->countAllResults() > 0;
    }

    
    protected function failureDescription($table): string
    {
        return sprintf(
            "a row in the table [%s] matches the attributes \n%s\n\n%s",
            $table,
            $this->toString(false, JSON_PRETTY_PRINT),
            $this->getAdditionalInfo($table),
        );
    }

    
    protected function getAdditionalInfo(string $table): string
    {
        $builder = $this->db->table($table);

        $similar = $builder->where(
            array_key_first($this->data),
            $this->data[array_key_first($this->data)],
        )->limit($this->show)->get()->getResultArray();

        if ($similar !== []) {
            $description = 'Found similar results: ' . json_encode($similar, JSON_PRETTY_PRINT);
        } else {
            
            $results = $this->db->table($table)
                ->limit($this->show)
                ->get()
                ->getResultArray();

            if ($results !== []) {
                return 'The table is empty.';
            }

            $description = 'Found: ' . json_encode($results, JSON_PRETTY_PRINT);
        }

        $total = $this->db->table($table)->countAll();
        if ($total > $this->show) {
            $description .= sprintf(' and %s others', $total - $this->show);
        }

        return $description;
    }

    
    public function toString(bool $exportObjects = false, $options = 0): string
    {
        return json_encode($this->data, $options);
    }
}
