<?php

namespace App\Models;

use CodeIgniter\Model;

class LaporanModel extends Model
{
    protected $table = 'pengajuan_cuti';
    protected $allowedFields  = [
        'pegawai_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'alasan',
        'total_hari',
        'status',
    ];

    public function getLaporan($perPage = null, $page = null, $search = null)
    {
        $builder = $this->db->table($this->table)
            ->join('pegawai', 'pegawai.id = ' . $this->table . '.pegawai_id')
            ->select($this->table . '.*, pegawai.nama, pegawai.nip')
            ->orderBy($this->table . '.id', 'DESC');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('pegawai.nama', $search)
                ->orLike('pegawai.nip', $search)
                ->orLike($this->table . '.alasan', $search)
                ->orLike($this->table . '.status', $search)
                ->orLike($this->table . '.tanggal_mulai', $search)
                ->orLike($this->table . '.tanggal_selesai', $search)
                ->groupEnd();
        }

        if ($perPage !== null && $page !== null) {
            return $builder->get($perPage, ($page - 1) * $perPage)->getResultArray();
        }

        return $builder->get()->getResultArray();
    }

    public function getRecentLaporan(int $limit = 5)
    {
        $builder = $this->db->table($this->table)
            ->join('pegawai', 'pegawai.id = ' . $this->table . '.pegawai_id')
            ->select($this->table . '.*, pegawai.nama, pegawai.nip')
            ->orderBy($this->table . '.tanggal_mulai', 'DESC')
            ->limit($limit);

        return $builder->get()->getResultArray();
    }

    public function countLaporan($search = null)
    {
        $builder = $this->db->table($this->table)
            ->join('pegawai', 'pegawai.id = ' . $this->table . '.pegawai_id');

        if (!empty($search)) {
            $builder->groupStart()
                ->like('pegawai.nama', $search)
                ->orLike('pegawai.nip', $search)
                ->orLike($this->table . '.alasan', $search)
                ->orLike($this->table . '.status', $search)
                ->orLike($this->table . '.tanggal_mulai', $search)
                ->orLike($this->table . '.tanggal_selesai', $search)
                ->groupEnd();
        }

        return $builder->countAllResults(false);
    }

    public function countCutiThisMonth()
    {
        $start = date('Y-m-01');
        $end = date('Y-m-t');

        return $this->builder()
            ->where('tanggal_mulai <=', $end)
            ->where('tanggal_selesai >=', $start)
            ->countAllResults();
    }

    public function countPendingRequests()
    {
        return $this->builder()
            ->whereIn('status', [
                'pending',
                'pending_teman',
                'pending_teman_sejawat',
                'pending_spv',
                'pending_hrd',
                'pending_direktur'
            ])
            ->countAllResults();
    }

    public function getNextApprovedLeave()
    {
        $today = date('Y-m-d');
        return $this->builder()
            ->join('pegawai', 'pegawai.id = ' . $this->table . '.pegawai_id')
            ->select($this->table . '.*, pegawai.nama, pegawai.nip')
            ->whereIn('status', ['approve', 'approved', 'diterima'])
            ->where('tanggal_mulai >=', $today)
            ->orderBy('tanggal_mulai', 'ASC')
            ->limit(1)
            ->get()
            ->getRowArray();
    }

    public function sumApprovedLeaveDays($year = null)
    {
        $year = $year ?: date('Y');
        $start = $year . '-01-01';
        $end   = $year . '-12-31';

        $result = $this->builder()
            ->selectSum('total_hari')
            ->whereIn('status', ['approve', 'approved', 'diterima'])
            ->where('tanggal_mulai >=', $start)
            ->where('tanggal_mulai <=', $end)
            ->get()
            ->getRowArray();

        return (int) ($result['total_hari'] ?? 0);
    }

    public function sumSickLeaveDays($year = null)
    {
        $year = $year ?: date('Y');
        $start = $year . '-01-01';
        $end   = $year . '-12-31';

        $result = $this->builder()
            ->selectSum('total_hari')
            ->whereIn('status', ['approve', 'approved', 'diterima'])
            ->like('alasan', 'sakit')
            ->where('tanggal_mulai >=', $start)
            ->where('tanggal_mulai <=', $end)
            ->get()
            ->getRowArray();

        return (int) ($result['total_hari'] ?? 0);
    }

    public function getUpcomingLeaves()
    {
        return $this->builder()
            ->join('pegawai', 'pegawai.id = ' . $this->table . '.pegawai_id')
            ->select($this->table . '.*, pegawai.nama AS nama_pegawai, pegawai.nip')
            ->whereIn('status', ['approve', 'approved', 'diterima'])
            ->orderBy('tanggal_mulai', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function calculateActualLeaveDays($startDate, $endDate)
    {
        $cutiModel = new Cuti_bersamaModel();

        $start = new \DateTime($startDate);
        $end = new \DateTime($endDate);
        $totalDays = $start->diff($end)->days + 1;

        $sharedHolidays = $cutiModel->getSharedHolidaysInRange($startDate, $endDate);
        $sharedHolidayCount = count($sharedHolidays);

        $actualLeaveDays = $totalDays - $sharedHolidayCount;

        return max(0, $actualLeaveDays);
    }
}
