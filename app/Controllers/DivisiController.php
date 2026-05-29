<?php

namespace App\Controllers;

use App\Models\DivisiModel;

class DivisiController extends BaseController
{
    protected $DivisiModel;

    public function __construct()
    {
        $this->DivisiModel = new DivisiModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Data Divisi',
            'divisi' => $this->DivisiModel->getDivisi()
        ];
        return view('divisi/index', $data);
    }

    public function create()
    {
            $data = [
                'title' => 'Tambah divisi',

            'validation' => session('validation') ?? \Config\Services::validation() 

        ];

        return view('divisi/create', $data);
    }

    public function save()
    {


if(!$this->validate([
    'nama_divisi' => 'required',
])) {
    $validation = \Config\Services::validation(); 
    return redirect()->to('/divisi/create')->withInput()->with('validation', $validation); 
}

        $this->DivisiModel->save([
            'nama_divisi' => $this->request->getVar('nama_divisi')
        ]);

        session()->setFlashdata('pesan', 'Data berhasil ditambahkan.');

        return redirect()->to('/divisi');
    }

    public function delete($id)
{
    $this->DivisiModel->delete($id);
    session()->setFlashdata('pesan', 'Data berhasil dihapus.');
    return redirect()->to('/divisi');
}

public function edit($id) 
{
    $data = [
            'title' => 'Ubah Divisi', 
            'validation' => session('validation') ?? \Config\Services::validation(), 

            'divisi' => $this->DivisiModel->getDivisi($id)   
        ];

        return view('divisi/edit', $data);
}

public function update($id)
{


if(!$this->validate([
    'nama_divisi' => 'required',
])) {
    $validation = \Config\Services::validation();
    return redirect()->to('/divisi/edit/' . $id)->withInput()->with('validation', $validation);
}

     $this->DivisiModel->save([
            'id' => $id,
            'nama_divisi' => $this->request->getVar('nama_divisi')
        ]);

        session()->setFlashdata('pesan', 'Data berhasil diubah.');

        return redirect()->to('/divisi');
}
}
