<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Cookie;
use Session;
use Redirect;
use Input;
use Validator;
use DB;
use Mail;
use App\Mail\SendMailable;
use Socialite;
use App\Models\User;
use App\Models\Show;

class ShowsController extends Controller {

    public function __construct() {
        
    }

    public function index() { 
        $pageTitle = 'Shows List';
        $page_heading = 'Shows';
        $activetab = 'actshow';
        $recordInfo = User::where('id', Session::get('user_id'))->first();

        return view('shows.index', ['title' => $pageTitle,'page_heading' => $page_heading, $activetab => 1, 'recordInfo' => $recordInfo]);
    }
    
    public function add() { 
        $pageTitle = 'Add New Show';
        $page_heading = 'Add Show';
        $activetab = 'actshow';
        $recordInfo = User::where('id', Session::get('user_id'))->first();

        return view('shows.add', ['title' => $pageTitle,'page_heading' => $page_heading, $activetab => 1, 'recordInfo' => $recordInfo]);
    }

}

?>