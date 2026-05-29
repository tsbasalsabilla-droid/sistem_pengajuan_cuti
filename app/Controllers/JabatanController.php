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
            $data = [
                'title' => 'Data jabatan',
                'jabatan' => $this->jabatanModel->getJabatan()
            ];
            return view('jabatan/index', $data);
        }

        public function create()
        {
            $data = [
                'title' => 'Tambah jabatan',

                'validation' => session('validation') ?? \Config\Services::validation()

            ];

            return view('jabatan/create', $data);
        }

    public function save()
    {


        if (!$this->validate([
            'jabatan' => 'required',
        ])) {
            $validation = \Config\Services::validation();
            return redirect()->to('/jabatan/create')->withInput()->with('validation', $validation);
        }

        $this->jabatanModel->save([
            'jabatan' => $this->request->getVar('jabatan')
        ]);

        session()->setFlashdata('pesan', 'Data berhasil ditambahkan.');

        return redirect()->to('/jabatan');
    }

    public function delete($id)
    {
        $this->jabatanModel->delete($id);
        session()->setFlashdata('pesan', 'Data berhasil dihapus.');
        return redirect()->to('/jabatan');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Ubah Jabatan',
            'validation' => session('validation') ?? \Config\Services::validation(),

            'jabatan' => $this->jabatanModel->getJabatan($id)
        ];

        return view('jabatan/edit', $data);
    }

    public function update($id)
    {


        if (!$this->validate([
            'jabatan' => 'required',
        ])) {
            $validation = \Config\Services::validation();
            return redirect()->to('/jabatan/edit/' . $id)->withInput()->with('validation', $validation);
        }

        $this->jabatanModel->save([
            'id' => $id,
            'jabatan' => $this->request->getVar('jabatan')
        ]);

        session()->setFlashdata('pesan', 'Data berhasil diubah.');

        return redirect()->to('/jabatan');
    }
}
