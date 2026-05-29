<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
<<<<<<< HEAD

/*
|--------------------------------------------------------------------------
| PEGAWAI
|--------------------------------------------------------------------------
*/

// DASHBOARD PEGAWAI
$routes->get('/pegawai/dashboard', 'DashboardController::index');

// CUTI PEGAWAI
$routes->get('/pegawai/cuti', 'CutiController::index');

$routes->get('/pegawai/cuti/create', 'CutiController::create');

$routes->post('/pegawai/cuti/store', 'CutiController::store');

$routes->get('/pegawai/cuti/detail/(:num)', 'CutiController::detail/$1');

$routes->post('/pegawai/cuti/approve/(:num)', 'CutiController::approve/$1');

$routes->post('/pegawai/cuti/reject/(:num)', 'CutiController::reject/$1');


/*
|--------------------------------------------------------------------------
| HRD
|--------------------------------------------------------------------------
*/

// DASHBOARD HRD
$routes->get('/hrd/dashboard', 'DashboardHrdController::index');

// CUTI HRD
$routes->get('/hrd/cuti', 'CutiHrdController::index');

$routes->get('/hrd/cuti/create', 'CutiHrdController::create');

$routes->post('/hrd/cuti/store', 'CutiHrdController::store');

$routes->get('/hrd/cuti/detail/(:num)', 'CutiHrdController::detail/$1');

$routes->post('/hrd/cuti/approve/(:num)', 'CutiHrdController::approve/$1');

$routes->post('/hrd/cuti/reject/(:num)', 'CutiHrdController::reject/$1');

/*
|--------------------------------------------------------------------------
| SPV
|--------------------------------------------------------------------------
*/

// DASHBOARD SPV
$routes->get('/spv/dashboard', 'DashboardSpvController::index');

// CUTI SPV
$routes->get('/spv/cuti', 'CutiSpvController::index');

$routes->get('/spv/cuti/create', 'CutiSpvController::create');

$routes->post('/spv/cuti/store', 'CutiSpvController::store');

$routes->get('/spv/cuti/detail/(:num)', 'CutiSpvController::detail/$1');

$routes->post('/spv/cuti/approve/(:num)', 'CutiSpvController::approve/$1');

$routes->post('/spv/cuti/reject/(:num)', 'CutiSpvController::reject/$1');
=======
$routes->get('/', 'Home::index');
$routes->get('/divisi', 'DivisiController::index');
$routes->get('divisi/create', 'DivisiController::create');
$routes->post('divisi/save', 'DivisiController::save');
$routes->get('divisi/edit/(:num)', 'DivisiController::edit/$1');
$routes->post('divisi/update/(:num)', 'DivisiController::update/$1');
$routes->get('divisi/delete/(:num)', 'DivisiController::delete/$1');
$routes->get('/jabatan', 'JabatanController::index');
$routes->get('jabatan/create', 'JabatanController::create');
$routes->post('jabatan/save', 'JabatanController::save');
$routes->get('jabatan/edit/(:num)', 'JabatanController::edit/$1');
$routes->post('jabatan/update/(:num)', 'JabatanController::update/$1');
$routes->get('jabatan/delete/(:num)', 'JabatanController::delete/$1');
$routes->get('/pegawai', 'PegawaiController::index');
$routes->get('pegawai/create', 'PegawaiController::create');
$routes->post('pegawai/save', 'PegawaiController::save');
$routes->get('pegawai/delete/(:num)', 'PegawaiController::delete/$1');
$routes->get('pegawai/edit/(:num)', 'PegawaiController::edit/$1');
$routes->post('pegawai/update/(:num)', 'PegawaiController::update/$1');
$routes->get('/cuti_bersama', 'Cuti_bersamaController::index');
$routes->get('cuti_bersama/create', 'Cuti_bersamaController::create');
$routes->post('cuti_bersama/save', 'Cuti_bersamaController::save');
$routes->get('cuti_bersama/delete/(:num)', 'Cuti_bersamaController::delete/$1');
$routes->get('cuti_bersama/edit/(:num)', 'Cuti_bersamaController::edit/$1');
$routes->post('cuti_bersama/update/(:num)', 'Cuti_bersamaController::update/$1');
$routes->get('/laporan', 'LaporanController::index');
$routes->get('laporan/delete/(:num)', 'LaporanController::delete/$1');
$routes->get('/dashboard', 'DashboardController::index');
$routes->get('/laporan/exportExcel', 'LaporanController::exportExcel');
>>>>>>> affbd2d3d63a4f9f5f53a92a1110c45dbd3682db
