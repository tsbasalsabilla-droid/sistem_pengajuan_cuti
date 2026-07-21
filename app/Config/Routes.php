<?php

use CodeIgniter\Router\RouteCollection;



$routes->get('/', 'Home::index');
$routes->get('/auth/login', 'Auth::login');
$routes->get('/auth/register', 'Auth::register');
$routes->post('/auth/doLogin', 'Auth::doLogin');
$routes->post('/auth/doRegister', 'Auth::doRegister');
$routes->get('/logout', 'Auth::logout');

$routes->get('/pegawai/dashboard', 'DashboardController::index');
$routes->get('/dashboard', 'DashboardController::index');

$routes->get('/pegawai/cuti', 'CutiController::index');
$routes->get('/cuti/history', 'CutiController::index');
$routes->get('/pegawai/cuti/create', 'CutiController::create');
$routes->get('/cuti', 'CutiController::create');
$routes->post('/pegawai/cuti/store', 'CutiController::store');
$routes->post('/cuti/store', 'CutiController::store');
$routes->get('/pegawai/cuti/detail/(:num)', 'CutiController::detail/$1');
$routes->get('/cuti/detail/(:num)', 'CutiController::detail/$1');

$routes->post('/pegawai/cuti/approve/(:num)', 'CutiController::approve/$1');
$routes->post('/cuti/approve/(:num)', 'CutiController::approve/$1');
$routes->post('/pegawai/cuti/reject/(:num)', 'CutiController::reject/$1');
$routes->post('/cuti/reject/(:num)', 'CutiController::reject/$1');
$routes->post('/pegawai/cuti/batal/(:num)', 'CutiController::batal/$1');
$routes->get('/teman', 'CutiController::teman');

$routes->get('/approval/approve-teman/(:num)', 'ApprovalController::approveTeman/$1');
$routes->get('/approval/reject-teman/(:num)', 'ApprovalController::rejectTeman/$1');

$routes->post('/approval/approve-teman/(:num)', 'ApprovalController::approveTeman/$1');
$routes->post('/approval/reject-teman/(:num)', 'ApprovalController::rejectTeman/$1');

$routes->group('spv/cuti', function ($routes) {
    $routes->get('/', 'CutiSpvController::index');
    $routes->get('create', 'CutiSpvController::create');
    $routes->post('store', 'CutiSpvController::store');
    $routes->get('detail/(:num)', 'CutiSpvController::detail/$1');
    $routes->post('batal/(:num)', 'CutiSpvController::batal/$1');
});

$routes->post('/spv/cuti/approve/(:num)', 'CutiSpvController::approve/$1');
$routes->post('/spv/cuti/reject/(:num)', 'CutiSpvController::reject/$1');

$routes->get('/spv/dashboard', 'Spv::dashboard');
$routes->get('/spv', 'Spv::dashboard');

$routes->get('/approvalspv', 'Spv::index');
$routes->get('/approval/approve-spv/(:num)', 'Spv::approve/$1');
$routes->post('/approval/approve-spv/(:num)', 'Spv::approve/$1');
$routes->post('/approval/reject-spv/(:num)', 'Spv::reject/$1');
$routes->get('/approval/reject-spv/(:num)', 'Spv::reject/$1');
$routes->get('/approval', 'Spv::dashboard');
$routes->get('/approval/index', 'Spv::dashboard');

$routes->get('/hrd/dashboard', 'DashboardHrdController::index');
$routes->get('/hrd/kalender-cuti', 'DashboardHrdController::cutiCalendar');
$routes->get('/hrd', 'DashboardHrdController::index');
$routes->get('/hrd/cuti', 'CutiHrdController::index');
$routes->get('/hrd/cuti/index', 'CutiHrdController::index');
$routes->get('/hrd/cuti/create', 'CutiHrdController::create');
$routes->post('/hrd/cuti/store', 'CutiHrdController::store');
$routes->get('/hrd/cuti/detail/(:num)', 'CutiHrdController::detail/$1');
$routes->post('/hrd/cuti/approve/(:num)', 'CutiHrdController::approve/$1');
$routes->post('/hrd/cuti/reject/(:num)', 'CutiHrdController::reject/$1');
$routes->post('/hrd/cuti/batal/(:num)', 'CutiHrdController::batal/$1');

$routes->get('/hrd/approve/(:num)', 'CutiController::approve/$1');
$routes->get('/approval/approve-hrd/(:num)', 'ApprovalhrdController::approve/$1');
$routes->post('/approval/approve-hrd/(:num)', 'ApprovalhrdController::approve/$1');
$routes->get('/approval/reject-hrd/(:num)', 'ApprovalController::rejectHrd/$1');
$routes->post('/approval/reject-hrd/(:num)', 'ApprovalController::rejectHrd/$1');
$routes->get('hrd/approvalhrd/indexhrd', 'ApprovalhrdController::index');

$routes->get('/hrd/divisi', 'DivisiController::index');
$routes->get('/hrd/divisi/create', 'DivisiController::create');
$routes->post('/hrd/divisi/save', 'DivisiController::save');
$routes->get('/hrd/divisi/edit/(:num)', 'DivisiController::edit/$1');
$routes->post('/hrd/divisi/update/(:num)', 'DivisiController::update/$1');
$routes->get('/hrd/divisi/delete/(:num)', 'DivisiController::delete/$1');
$routes->get('/divisi', 'DivisiController::index');
$routes->get('divisi/create', 'DivisiController::create');
$routes->post('divisi/save', 'DivisiController::save');
$routes->get('divisi/edit/(:num)', 'DivisiController::edit/$1');
$routes->post('divisi/update/(:num)', 'DivisiController::update/$1');
$routes->get('divisi/delete/(:num)', 'DivisiController::delete/$1');

$routes->get('/hrd/jabatan', 'JabatanController::index');
$routes->get('/hrd/jabatan/create', 'JabatanController::create');
$routes->post('/hrd/jabatan/save', 'JabatanController::save');
$routes->get('/hrd/jabatan/edit/(:num)', 'JabatanController::edit/$1');
$routes->post('/hrd/jabatan/update/(:num)', 'JabatanController::update/$1');
$routes->get('/hrd/jabatan/delete/(:num)', 'JabatanController::delete/$1');
$routes->get('jabatan', 'JabatanController::index');
$routes->get('jabatan/create', 'JabatanController::create');
$routes->post('jabatan/save', 'JabatanController::save');
$routes->get('jabatan/edit/(:num)', 'JabatanController::edit/$1');
$routes->post('jabatan/update/(:num)', 'JabatanController::update/$1');
$routes->get('jabatan/delete/(:num)', 'JabatanController::delete/$1');

$routes->get('/hrd/pegawai', 'PegawaiController::index');
$routes->get('/hrd/pegawai/create', 'PegawaiController::create');
$routes->post('/hrd/pegawai/save', 'PegawaiController::save');
$routes->get('/hrd/pegawai/delete/(:num)', 'PegawaiController::delete/$1');
$routes->get('/hrd/pegawai/edit/(:num)', 'PegawaiController::edit/$1');
$routes->post('/hrd/pegawai/update/(:num)', 'PegawaiController::update/$1');
$routes->get('/pegawai', 'PegawaiController::index');
$routes->get('pegawai/create', 'PegawaiController::create');
$routes->post('pegawai/save', 'PegawaiController::save');
$routes->get('pegawai/delete/(:num)', 'PegawaiController::delete/$1');
$routes->get('pegawai/edit/(:num)', 'PegawaiController::edit/$1');
$routes->post('pegawai/update/(:num)', 'PegawaiController::update/$1');

$routes->get('/hrd/cuti_bersama', 'Cuti_bersamaController::index');
$routes->get('/hrd/cuti_bersama/create', 'Cuti_bersamaController::create');
$routes->post('/hrd/cuti_bersama/save', 'Cuti_bersamaController::save');
$routes->get('/hrd/cuti_bersama/delete/(:num)', 'Cuti_bersamaController::delete/$1');
$routes->get('/hrd/cuti_bersama/edit/(:num)', 'Cuti_bersamaController::edit/$1');
$routes->post('/hrd/cuti_bersama/update/(:num)', 'Cuti_bersamaController::update/$1');
$routes->get('cuti_bersama', 'Cuti_bersamaController::index');
$routes->get('cuti_bersama/create', 'Cuti_bersamaController::create');
$routes->post('cuti_bersama/save', 'Cuti_bersamaController::save');
$routes->get('cuti_bersama/delete/(:num)', 'Cuti_bersamaController::delete/$1');
$routes->get('cuti_bersama/edit/(:num)', 'Cuti_bersamaController::edit/$1');
$routes->post('cuti_bersama/update/(:num)', 'Cuti_bersamaController::update/$1');

$routes->get('/hrd/laporan', 'LaporanController::index');
$routes->get('/hrd/laporan/delete/(:num)', 'LaporanController::delete/$1');
$routes->get('/hrd/laporan/exportExcel', 'LaporanController::exportExcel');
$routes->get('laporan', 'LaporanController::index');
$routes->get('laporan/delete/(:num)', 'LaporanController::delete/$1');
$routes->get('laporan/exportExcel', 'LaporanController::exportExcel');

$routes->get('/direktur', 'ApprovalController::index');
$routes->get('/direktur/approve/(:num)', 'Direktur::approve/$1');
$routes->get('/approval/approve-direktur/(:num)', 'ApprovalController::approveDirektur/$1');
$routes->get('/direktur/reject/(:num)', 'Direktur::reject/$1');
$routes->get('/approval/reject-direktur/(:num)', 'ApprovalController::rejectDirektur/$1');
$routes->post('/approval/reject-direktur/(:num)', 'ApprovalController::rejectDirektur/$1');
$routes->get('/direktur/dashboard', 'Direktur::dashboard');
