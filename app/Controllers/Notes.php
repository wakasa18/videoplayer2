<?php

namespace App\Controllers;

class Notes extends BaseController
{
    protected $helpers = ['url'];

    public function index(): string
    {
        return view('coming_soon', [
            'pageTitle'   => 'Notes · Damon\'s Archive',
            'eyebrow'     => 'Field Notes · Sector 21',
            'heading'     => 'Notes',
            'description' => "Quick write-ups and references will live here. Not built yet.",
            'icon'        => '&#128221;',
            'backUrl'     => base_url('others'),
            'backLabel'   => 'Others',
        ]);
    }
}
