<?php

namespace App\Controllers;

use App\Models\Cuti_bersamaModel;

class Cuti_bersamaController extends BaseController
{
    protected $cuti_bersamaModel;

    public function __construct()
    {
        $this->cuti_bersamaModel = new Cuti_bersamaModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Data Cuti Bersama',
            'cuti_bersama' => $this->cuti_bersamaModel->getcuti()
        ];
        return view('hrd/cuti_bersama/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Cuti Bersama',

            'validation' => session('validation') ?? \Config\Services::validation()

        ];

        return view('hrd/cuti_bersama/create', $data);
    }

    public function save()
    {


        if (!$this->validate([
            'tanggal' => 'required',
            'keterangan' => 'required',
        ])) {
            $validation = \Config\Services::validation();
            return redirect()->to('/hrd/cuti_bersama/create')->withInput()->with('validation', $validation);
        }

        $this->cuti_bersamaModel->save([
            'tanggal' => $this->request->getVar('tanggal'),
            'keterangan' => $this->request->getVar('keterangan')
        ]);

        session()->setFlashdata('pesan', 'Data berhasil ditambahkan.');

        return redirect()->to('/hrd/cuti_bersama');
    }

    public function delete($id)
    {
        $this->cuti_bersamaModel->delete($id);
        session()->setFlashdata('pesan', 'Data berhasil dihapus.');
        return redirect()->to('/hrd/cuti_bersama');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Ubah Cuti Bersama',
            'validation' => session('validation') ?? \Config\Services::validation(),

            'cuti_bersama' => $this->cuti_bersamaModel->getcuti($id)
        ];

        return view('hrd/cuti_bersama/edit', $data);
    }

    public function update($id)
    {


        if (!$this->validate([
            'tanggal' => 'required',
            'keterangan' => 'required',
        ])) {
            $validation = \Config\Services::validation();
            return redirect()->to('/hrd/cuti_bersama/edit/' . $id)->withInput()->with('validation', $validation);
        }

        $this->cuti_bersamaModel->save([
            'id' => $id,
            'tanggal' => $this->request->getVar('tanggal'),
            'keterangan' => $this->request->getVar('keterangan')
        ]);

        session()->setFlashdata('pesan', 'Data berhasil diubah.');

        return redirect()->to('/hrd/cuti_bersama');
    }
}
