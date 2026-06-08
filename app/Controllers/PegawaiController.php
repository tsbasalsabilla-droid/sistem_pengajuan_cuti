<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\JabatanModel;
use App\Models\DivisiModel;

class pegawaiController extends BaseController
{
    protected $UserModel;
    protected $JabatanModel;
    protected $DivisiModel;

    public function __construct()
    {
        $this->UserModel = new UserModel();
        $this->JabatanModel = new JabatanModel();
        $this->DivisiModel = new DivisiModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Data Pegawai',
            'pegawai' => $this->UserModel->getPegawai(),
        ];
        return view('pegawai/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Pegawai',
            'validation' => session('validation') ?? \Config\Services::validation(),
            'jabatan' => $this->JabatanModel->findAll(),
            'divisi' => $this->DivisiModel->findAll(),

        ];

        return view('pegawai/create', $data);
    }

    public function save()
    {


        $rules = [
            'nama'       => 'required',
            'nip'        => 'required',
            'email'      => 'required',
            'id_jabatan' => 'required',
            'id_divisi'  => 'required',
            'no_hp'      => 'required',
            'alamat'     => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->to('/hrd/pegawai/create')
                ->withInput()
                ->with('validation', \Config\Services::validation());
        }

        $this->UserModel->save([
            'nama' => $this->request->getVar('nama'),
            'nip' => $this->request->getVar('nip'),
            'email' => $this->request->getVar('email'),
            'id_jabatan' => $this->request->getVar('id_jabatan'),
            'id_divisi' => $this->request->getVar('id_divisi'),
            'no_hp' => $this->request->getVar('no_hp'),
            'alamat' => $this->request->getVar('alamat'),
            'foto' => $this->request->getVar('foto'),
        ]);

        session()->setFlashdata('pesan', 'Data berhasil ditambahkan.');

        return redirect()->to('/hrd/pegawai');
    }

    public function delete($id)
    {
        $this->UserModel->delete($id);
        session()->setFlashdata('pesan', 'Data berhasil dihapus.');
        return redirect()->to('/hrd/pegawai');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Ubah pegawai',
            'validation' => session('validation') ?? \Config\Services::validation(),

            'pegawai' => $this->UserModel->getPegawai($id),
            'jabatan' => $this->JabatanModel->findAll(),
            'divisi' => $this->DivisiModel->findAll(),
        ];

        return view('pegawai/edit', $data);
    }

    public function update($id)
    {

        $rules = [
            'nama'       => 'required',
            'nip'        => 'required',
            'email'      => 'required',
            'id_jabatan' => 'required',
            'id_divisi'  => 'required',
            'no_hp'      => 'required',
            'alamat'     => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->to('/hrd/pegawai/edit/' . $id)
                ->withInput()
                ->with('validation', \Config\Services::validation());
        }

        $this->UserModel->save([
            'id' => $id,
            'nama' => $this->request->getVar('nama'),
            'nip' => $this->request->getVar('nip'),
            'email' => $this->request->getVar('email'),
            'id_jabatan' => $this->request->getVar('id_jabatan'),
            'id_divisi' => $this->request->getVar('id_divisi'),
            'no_hp' => $this->request->getVar('no_hp'),
            'alamat' => $this->request->getVar('alamat'),
            'foto' => $this->request->getVar('foto'),
        ]);

        session()->setFlashdata('pesan', 'Data berhasil diubah.');

        return redirect()->to('/hrd/pegawai');
    }
}
