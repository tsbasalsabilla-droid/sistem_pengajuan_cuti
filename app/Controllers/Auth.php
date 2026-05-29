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

    // Form login
    public function login()
    {
        return view('auth/login');
    }

    // Proses login
    public function doLogin()
    {
        $nip = $this->request->getPost('nip');
        $password = $this->request->getPost('password');

        $user = $this->pegawaiModel->where('nip', $nip)->first();

        if ($user && password_verify($password, $user['password'])) {
            // Simpan user ke session
            $this->session->set('user', [
                'id' => $user['id'],
                'nama' => $user['nama'],
                'email' => $user['email'],
                'nip' => $user['nip'],
                'role' => $user['role']
            ]);

            // Redirect berdasarkan role
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
                    $redirectTo = '/cuti';
                    break;
            }

            return redirect()->to($redirectTo)->with('success', 'Login berhasil!');
        }

        return redirect()->back()->with('error', 'Email atau password salah!');
    }

    // Logout
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/auth/login')->with('success', 'Logout berhasil!');
    }

    // Register (optional)
    public function register()
    {
        return view('auth/register');
    }

    // Proses register
    public function doRegister()
    {
        $data = [
            'nama' => $this->request->getPost('nama'),
            'email' => $this->request->getPost('email'),
            'nip' => $this->request->getPost('nip'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => $this->request->getPost('role') ?? 'karyawan',
            'status_aktif' => 'aktif'
        ];

        $this->pegawaiModel->save($data);

        return redirect()->to('/auth/login')->with('success', 'Registrasi berhasil! Silakan login.');
    }
}
