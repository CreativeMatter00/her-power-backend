<?php

namespace App\Http\Controllers;

abstract class Controller
{
    protected $baseURL;
    public function __construct()
    {
        $this->baseURL = asset('/public');
    }
}
