<?php

namespace App\Http\Controllers;
use Response;
use App\Models\User;
use Auth;
Use DB;

use Illuminate\Http\Request;

class AuthController extends Controller
{

    public function logout(Request $request) {
        
        $user = Auth::user();
        
        $user->tokens()->delete();

        return Response::json([
            'respuesta' => true
        ]);

    }

    public function inactivateuser(Request $request) {

        $user = User::find($request->user_id);

        $user->tokens()->delete();

        $user->baneado = 1;
        $user->baneado_por = Auth::user()->id;
        $user->baneado_fec = Date('Y-m-d H:i');
        $user->save();

        return Response::json([
            'response' => true,
            'user' => $user
        ]);

    }

    public function activateuser(Request $request) {

        $user = User::find($request->user_id);

        $user->baneado = null;
        $user->baneado_por = null;
        $user->baneado_fec = null;
        $user->activado_por = Auth::user()->id;
        $user->save();

        return Response::json([
            'response' => true,
            'user' => $user
        ]);

    }

}
