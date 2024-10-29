<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Response;
use DB;

class CostcenterController extends Controller
{
    
    /*Start Centrosdecosto*/

    public function create(Request $request) {

        return Response::json([
            'response' => true
        ]);

    }

    public function edit(Request $request) {

        return Response::json([
            'response' => true
        ]);

    }

    public function list(Request $request) {

        $centrosdecosto = DB::table('centrosdecosto')
        ->whereNull('inactivo')
        ->whereNull('inactivo_total')
        ->get();

        return Response::json([
            'response' => true,
            'costcenter' => $centrosdecosto
        ]);

    }

    /*End Centrosdecosto*/

    /*Start Subcentrosdecosto*/

    public function createsub(Request $request) {

        return Response::json([
            'response' => true
        ]);

    }

    public function editsub(Request $request) {

        return Response::json([
            'response' => true
        ]);

    }

    public function listsub(Request $request) {

        $subcentrosdecosto = DB::table('subcentrosdecosto')->get();

        return Response::json([
            'response' => true,
            'subcostcenter' => $subcentrosdecosto
        ]);

    }
}
