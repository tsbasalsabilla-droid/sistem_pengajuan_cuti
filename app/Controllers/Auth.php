<?php

namespace App\Controllers;

use App\Models\PegawaiModel;

class Auth extends BaseController
{
    protected $pegawaiModel;

    public function __construct()
    {
        $this->pegawaiModel = new PegawaiModel();
    }


    public function login()
    {
        return view('auth/login');
    }


    public function doLogin()
    {
        $rules = [
            'nip' => 'required',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'NIP dan password wajib diisi.');
        }

        $nip = $this->request->getPost('nip');
        $password = $this->request->getPost('password');

        $user = $this->pegawaiModel->where('nip', $nip)->first();

        if (!$user || !password_verify($password, $user['password'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'NIP atau password salah.');
        }

        if (isset($user['status_aktif']) && $user['status_aktif'] !== 'aktif') {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Akun Anda tidak aktif. Hubungi administrator.');
        }

        $this->session->set('user', [
            'id' => $user['id'],
            'nama' => $user['nama'],
            'email' => $user['email'],
            'nip' => $user['nip'],
            'role' => $user['role'],
        ]);

        switch ($user['role']) {
            case 'spv':
                $redirectTo = '/spv/dashboard';
                break;
            case 'hrd':
                $redirectTo = '/hrd';
                break;
            case 'direktur':
                $redirectTo = '/direktur/dashboard';
                break;
            default:
                $redirectTo = '/dashboard';
                break;
        }

        return redirect()->to($redirectTo)->with('success', 'Login berhasil!');
    }


    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/auth/login')->with('success', 'Logout berhasil!');
    }


    public function register()
    {
        return view('auth/register');
    }


    public function doRegister()
    {
        $rules = [
            'nama' => 'required',
            'email' => 'required|valid_email|is_unique[pegawai.email]',
            'nip' => 'required|is_unique[pegawai.nip]',
            'password' => 'required|min_length[8]'
        ];

        $messages = [
            'email' => [
                'is_unique' => 'Email sudah terdaftar.',
                'valid_email' => 'Format email tidak valid.',
            ],
            'nip' => [
                'is_unique' => 'NIP sudah terdaftar.',
            ],
            'password' => [
                'min_length' => 'Password minimal 8 karakter.',
            ],
        ];

        if (!$this->validate($rules, $messages)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $this->pegawaiModel->save([
            'nama' => $this->request->getPost('nama'),
            'email' => $this->request->getPost('email'),
            'nip' => $this->request->getPost('nip'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => 'karyawan',
            'status_aktif' => 'aktif',
        ]);

        return redirect()->to('/auth/login')->with('success', 'Registrasi berhasil! Silakan login.');
    }
}
