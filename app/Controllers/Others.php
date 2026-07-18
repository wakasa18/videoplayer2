<?php

namespace App\Controllers;

class Others extends BaseController
{
    protected $helpers = ['url'];

    public function index(): string
    {
        return view('others/index');
    }
}
