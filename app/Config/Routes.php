<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
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
