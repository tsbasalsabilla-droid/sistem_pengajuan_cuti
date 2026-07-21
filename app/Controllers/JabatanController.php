<?php

namespace App\Controllers;

use App\Models\JabatanModel;

class JabatanController extends BaseController
{
    protected $jabatanModel;

    public function __construct()
    {
        $this->jabatanModel = new JabatanModel();
    }

    public function index()
    {
        $page = max(1, (int) ($this->request->getVar('page') ?? 1));
        $perPage = 10;
        $total = $this->jabatanModel->countAllResults();
        $totalPages = max(1, (int) ceil($total / $perPage));

        $data = [
            'title' => 'Data jabatan',
            'jabatan' => $this->jabatanModel->getJabatan(false, $perPage, $page),
            'pager' => $this->jabatanModel->pager,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
        ];
        return view('hrd/jabatan/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah jabatan',

            'validation' => session('validation') ?? \Config\Services::validation()

        ];

        return view('hrd/jabatan/create', $data);
    }

    public function save()
    {


        if (!$this->validate([
            'jabatan' => 'required',
        ])) {
            $validation = \Config\Services::validation();
            return redirect()->to('/hrd/jabatan/create')->withInput()->with('validation', $validation);
        }

        $this->jabatanModel->save([
            'jabatan' => $this->request->getVar('jabatan')
        ]);

        session()->setFlashdata('pesan', 'Data berhasil ditambahkan.');

        return redirect()->to('/hrd/jabatan');
    }

    public function delete($id)
    {
        $this->jabatanModel->delete($id);
        session()->setFlashdata('pesan', 'Data berhasil dihapus.');
        return redirect()->to('/hrd/jabatan');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Ubah Jabatan',
            'validation' => session('validation') ?? \Config\Services::validation(),

            'jabatan' => $this->jabatanModel->getJabatan($id)
        ];

        return view('hrd/jabatan/edit', $data);
    }

    public function update($id)
    {


        if (!$this->validate([
            'jabatan' => 'required',
        ])) {
            $validation = \Config\Services::validation();
            return redirect()->to('/hrd/jabatan/edit/' . $id)->withInput()->with('validation', $validation);
        }

        $this->jabatanModel->save([
            'id' => $id,
            'jabatan' => $this->request->getVar('jabatan')
        ]);

        session()->setFlashdata('pesan', 'Data berhasil diubah.');

        return redirect()->to('/hrd/jabatan');
    }
}
