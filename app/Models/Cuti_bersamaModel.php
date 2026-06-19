<?php

namespace App\Models;

use CodeIgniter\Model;

class Cuti_bersamaModel extends Model
{
    protected $table = 'cuti_bersama';
    protected $allowedFields  = [
        'tanggal',
        'keterangan',
    ];

    public function getcuti($id = false)
    {
        if ($id == false) {
            return $this->findAll();
        }

        return $this->where(['id' => $id])->first();
    }

    public function getAllSharedHolidayDates()
    {
        return $this->select('tanggal')
            ->findAll();
    }

    public function isSharedHoliday($date)
    {
        return $this->where('tanggal', $date)->first() !== null;
    }

    public function countSharedHolidaysInRange($startDate, $endDate)
    {
        return $this->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->countAllResults();
    }

    public function getSharedHolidaysInRange($startDate, $endDate)
    {
        return $this->where('tanggal >=', $startDate)
            ->where('tanggal <=', $endDate)
            ->select('tanggal')
            ->findAll();
    }
}
