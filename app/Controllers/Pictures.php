<?php

namespace App\Controllers;

class Pictures extends BaseController
{
    protected $helpers = ['url'];

    public function index(): string
    {
        return view('coming_soon', [
            'pageTitle'   => 'Pictures · Damon\'s Archive',
            'eyebrow'     => 'Photo Archive · Sector 12',
            'heading'     => 'Pictures',
            'description' => "The photo gallery isn't wired up yet — uploading, albums, and a lightbox view are next on the build list.",
            'icon'        => '&#9737;',
            'backUrl'     => base_url('/'),
            'backLabel'   => 'Home',
        ]);
    }
}
