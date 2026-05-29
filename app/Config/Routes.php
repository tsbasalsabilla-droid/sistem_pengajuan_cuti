<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Auth Routes
$routes->get('/auth/login', 'Auth::login');
$routes->get('/auth/register', 'Auth::register');
$routes->post('/auth/doLogin', 'Auth::doLogin');
$routes->post('/auth/doRegister', 'Auth::doRegister');
$routes->get('/logout', 'Auth::logout');

// Main Routes
$routes->get('/', 'Home::index');
$routes->get('/cuti', 'Cuti::index');
$routes->post('/cuti/submit', 'Cuti::submit');
$routes->get('/cuti/history', 'Cuti::history');
$routes->get('/spv', 'Spv::index');
$routes->get('/spv/approve/(:num)', 'Spv::approve/$1');
$routes->get('/spv/reject/(:num)', 'Spv::reject/$1');
$routes->get('/teman', 'Cuti::teman');
$routes->get('/teman/approve/(:num)', 'Cuti::approveTeman/$1');
$routes->get('/teman/reject/(:num)', 'Cuti::rejectTeman/$1');
$routes->get('/hrd', 'Cuti::hrd');
$routes->get('/hrd/approve/(:num)', 'Cuti::approveHrd/$1');
$routes->get('/hrd/reject/(:num)', 'Cuti::rejectHrd/$1');
$routes->get('/direktur', 'Direktur::index');
$routes->get('/direktur/approve/(:num)', 'Direktur::approve/$1');
$routes->get('/direktur/reject/(:num)', 'Direktur::reject/$1');
$routes->get('/spv/dashboard', 'Spv::dashboard');
$routes->get('/direktur/dashboard', 'Direktur::dashboard');
