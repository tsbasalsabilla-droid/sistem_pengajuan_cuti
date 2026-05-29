<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

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
