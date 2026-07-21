<?php

declare(strict_types=1);



namespace CodeIgniter\RESTful;

use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;


class ResourceController extends BaseResource
{
    use ResponseTrait;

    
    public function index()
    {
        return $this->fail(lang('RESTful.notImplemented', ['index']), 501);
    }

    
    public function show($id = null)
    {
        return $this->fail(lang('RESTful.notImplemented', ['show']), 501);
    }

    
    public function new()
    {
        return $this->fail(lang('RESTful.notImplemented', ['new']), 501);
    }

    
    public function create()
    {
        return $this->fail(lang('RESTful.notImplemented', ['create']), 501);
    }

    
    public function edit($id = null)
    {
        return $this->fail(lang('RESTful.notImplemented', ['edit']), 501);
    }

    
    public function update($id = null)
    {
        return $this->fail(lang('RESTful.notImplemented', ['update']), 501);
    }

    
    public function delete($id = null)
    {
        return $this->fail(lang('RESTful.notImplemented', ['delete']), 501);
    }

    
    public function setFormat(string $format = 'json')
    {
        if (in_array($format, ['json', 'xml'], true)) {
            $this->format = $format;
        }
    }
}
