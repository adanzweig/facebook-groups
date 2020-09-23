<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->middleware('auth');
        return view('home');
    }
    public function test(){

        if(Storage::disk('local')->exists('Netatmo.log')){
            Storage::disk('local')->append('Netatmo.log', json_encode($_REQUEST));
        }else{
            Storage::disk('local')->put('Netatmo.log', '\n'.json_encode($_REQUEST));

        }

    }
}
