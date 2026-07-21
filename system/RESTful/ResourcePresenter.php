<?php

declare(strict_types=1);



namespace CodeIgniter\RESTful;

use CodeIgniter\HTTP\ResponseInterface;


class ResourcePresenter extends BaseResource
{
    
    public function index()
    {
        return lang('RESTful.notImplemented', ['index']);
    }

    
    public function show($id = null)
    {
        return lang('RESTful.notImplemented', ['show']);
    }

    
    public function new()
    {
        return lang('RESTful.notImplemented', ['new']);
    }

    
    public function create()
    {
        return lang('RESTful.notImplemented', ['create']);
    }

    
    public function edit($id = null)
    {
        return lang('RESTful.notImplemented', ['edit']);
    }

    
    public function update($id = null)
    {
        return lang('RESTful.notImplemented', ['update']);
    }

    
    public function remove($id = null)
    {
        return lang('RESTful.notImplemented', ['remove']);
    }

    
    public function delete($id = null)
    {
        return lang('RESTful.notImplemented', ['delete']);
    }
}
