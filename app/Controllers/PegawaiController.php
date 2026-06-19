<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\JabatanModel;
use App\Models\DivisiModel;
use App\Models\PengajuanCutiModel;
use App\Models\SaldoCutiModel;
use CodeIgniter\I18n\Time;

class PegawaiController extends BaseController
{
    protected $UserModel;
    protected $JabatanModel;
    protected $DivisiModel;
    protected $CutiModel;
    protected $SaldoCutiModel;

    public function __construct()
    {
        $this->UserModel      = new UserModel();
        $this->JabatanModel   = new JabatanModel();
        $this->DivisiModel    = new DivisiModel();
        $this->CutiModel      = new PengajuanCutiModel();
        $this->SaldoCutiModel = new SaldoCutiModel();
    }

    public function index()
    {
        $this->SaldoCutiModel->syncSaldoCuti();

        $userId = session()->get('user')['id'] ?? null;

        $data = [
            'title'   => 'Data Pegawai',
            'pegawai' => $this->UserModel->getPegawai(),
            'cuti'    => []
        ];

        $data['cuti'] = $this->CutiModel
            ->where('status', 'pending_teman_sejawat')
            ->where('pegawai_id !=', $userId)
            ->findAll();

        return view('hrd/pegawai/index', $data);
    }

    public function create()
    {
        $data = [
            'title' => 'Tambah Pegawai',
            'validation' => session('validation') ?? \Config\Services::validation(),
            'jabatan' => $this->JabatanModel->findAll(),
            'divisi' => $this->DivisiModel->findAll(),
        ];

        return view('hrd/pegawai/create', $data);
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
            return redirect()->to('hrd/pegawai/create')
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
            'password' => password_hash('123', PASSWORD_DEFAULT),
            'role' => 'pegawai',
        ]);


        session()->setFlashdata('pesan', 'Data pegawai berhasil ditambahkan.');

        return redirect()->to('hrd/pegawai');
    }

    public function delete($id)
    {
        $this->UserModel->delete($id);
        session()->setFlashdata('pesan', 'Data berhasil dihapus.');
        return redirect()->to('hrd/pegawai');
    }

    public function edit($id)
    {
        $data = [
            'title' => 'Ubah pegawai',
            'validation' => session('validation') ?? \Config\Services::validation(),
            'pegawai' => $this->UserModel->getPegawai($id) ?? $this->UserModel->find($id),
            'jabatan' => $this->JabatanModel->findAll(),
            'divisi' => $this->DivisiModel->findAll(),
        ];

        return view('hrd/pegawai/edit', $data);
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
            return redirect()->to('hrd/pegawai/edit/' . $id)
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

        return redirect()->to('hrd/pegawai');
    }
}
