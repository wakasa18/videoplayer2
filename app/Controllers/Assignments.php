<?php

namespace App\Controllers;

class Assignments extends BaseController
{
    protected $helpers = ['url'];

    public function index(): string
    {
        return view('coming_soon', [
            'pageTitle'   => 'Assignments · Damon\'s Archive',
            'eyebrow'     => 'Task Log · Sector 22',
            'heading'     => 'Assignments',
            'description' => "Coursework and tasks, tracked from assigned to done. Not built yet.",
            'icon'        => '&#128203;',
            'backUrl'     => base_url('others'),
            'backLabel'   => 'Others',
        ]);
    }
}
