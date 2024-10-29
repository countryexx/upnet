<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\CostcenterController;
use App\Http\Controllers\TasksController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::post('auth/inactivateuser', [AuthController::class, 'inactivateuser']);

    Route::post('auth/activateuser', [AuthController::class, 'activateuser']);
    
    Route::post('users/createuser', [UsersController::class, 'createuser']);

    Route::post('users/edituser', [UsersController::class, 'edituser']);

    Route::post('users/listusers', [UsersController::class, 'listusers']);

    Route::post('users/listemploy', [UsersController::class, 'listemploy']);

    Route::post('changepassword', [UsersController::class, 'changepassword']);

    Route::post('permissionsrol', [UsersController::class, 'permissionsrol']);

    Route::post('seeuserol', [UsersController::class, 'seeuserol']);

    Route::post('changeuserrol', [UsersController::class, 'changeuserrol']);

    Route::post('createrol', [UsersController::class, 'createrol']);

    Route::post('editrol', [UsersController::class, 'editrol']);

    Route::post('users/listentity', [UsersController::class, 'listentity']);
    Route::post('users/listentityuser', [UsersController::class, 'listentityuser']);

    Route::post('users/listtypeuser', [UsersController::class, 'listtypeuser']);

    Route::post('users/listprofile', [UsersController::class, 'listprofile']);

    Route::post('costcenter/create', [CostcenterController::class, 'create']);
    Route::post('costcenter/edit', [CostcenterController::class, 'edit']);
    Route::get('costcenter/list', [CostcenterController::class, 'list']);

    Route::post('costcenter/subcostcenter/createsub', [CostcenterController::class, 'createsub']);
    Route::post('costcenter/subcostcenter/editsub', [CostcenterController::class, 'editsub']);
    Route::get('costcenter/subcostcenter/listsub', [CostcenterController::class, 'listsub']);

    Route::get('users/download', [UsersController::class, 'download']);

    Route::post('tasks/statuslist', [TasksController::class, 'statuslist']);
    Route::post('tasks/statuslistselected', [TasksController::class, 'statuslistselected']);
    Route::post('tasks/creategroup', [TasksController::class, 'creategroup']);
    Route::post('tasks/listgroups', [TasksController::class, 'listgroups']);
    Route::post('tasks/createproject', [TasksController::class, 'createproject']);
    Route::post('tasks/listprojects', [TasksController::class, 'listprojects']);

    Route::post('tasks/createsubproject', [TasksController::class, 'createsubproject']);
    Route::post('tasks/listsubprojects', [TasksController::class, 'listsubprojects']);

    Route::post('tasks/deleteproject', [TasksController::class, 'deleteproject']);
    Route::post('tasks/deletesubproject', [TasksController::class, 'deletesubproject']);

    Route::post('tasks/listresponsible', [TasksController::class, 'listresponsible']);
    Route::post('tasks/createevidenceproject', [TasksController::class, 'createevidenceproject']);
    Route::post('tasks/createevidencesubproject', [TasksController::class, 'createevidencesubproject']);

    Route::post('tasks/listevidenceproject', [TasksController::class, 'listevidenceproject']);
    Route::post('tasks/listevidencesubproject', [TasksController::class, 'listevidencesubproject']);

    Route::post('tasks/editpriority', [TasksController::class, 'editpriority']);
    Route::post('tasks/editprioritysub', [TasksController::class, 'editprioritysub']);

    Route::post('tasks/editstatus', [TasksController::class, 'editstatus']);
    Route::post('tasks/editstatussub', [TasksController::class, 'editstatussub']);

    Route::post('tasks/editresponsible', [TasksController::class, 'editresponsible']);
    Route::post('tasks/editresponsiblesub', [TasksController::class, 'editresponsiblesub']);

    Route::post('tasks/editordengroup', [TasksController::class, 'editordengroup']);
    Route::post('tasks/editordenproject', [TasksController::class, 'editordenproject']);
    Route::post('tasks/editordensubproject', [TasksController::class, 'editordensubproject']);
    
    Route::post('tasks/listprojectsuser', [TasksController::class, 'listprojectsuser']);
    Route::post('tasks/createnotification', [TasksController::class, 'createnotification']);
    
    Route::post('tasks/readnotification', [TasksController::class, 'readnotification']);
    Route::post('tasks/readnotifications', [TasksController::class, 'readnotifications']);
    
    Route::post('tasks/listnotifications', [TasksController::class, 'listnotifications']);

    Route::post('tasks/deletenotification', [TasksController::class, 'deletenotification']);
    Route::post('tasks/deletenotifications', [TasksController::class, 'deletenotifications']);

    Route::post('tasks/push', [TasksController::class, 'push']); //Prueba de notificación Pusher

});

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('users/samu', [UsersController::class, 'query']);

});

Route::post('/login', function (Request $request) {

    $usuario = DB::table('users')
    ->where('username',$request->username)
    ->first();

    if($usuario==null) {

        return Response::json([
            'response' => false,
            'message' => 'Este usuario no se encuentra registrado en el sistema. Póngase en contacto con el administrador del sistema o con el personal de soporte técnico.'
        ]);

    }else{

        $credentials = $request->validate([
            'username' => [''],
            'password' => [''],
        ]);

        if (Auth::attempt($credentials)) {
    
            $user = Auth::user();
            
            if($user->baneado==1) {

                return Response::json([
                    'response' => false,
                    'message' => 'Este usuario está desactivado. Póngase en contacto con el administrador del sistema o con el personal de soporte técnico.'
                ]);

            }else{

                $token = $user->createToken('auth_token')->plainTextToken;

                Auth::logoutOtherDevices($request->password);
                
                $update = DB::table('users')
                ->where('id',$user->id)
                ->update([
                    'last_login' => date('Y-m-d H:i')
                ]);
                
                $user = DB::table('users')
                ->leftjoin('perfil', 'perfil.id', '=', 'users.id_perfil')
                ->select('users.*', 'perfil.nombre as nombre_perfil')
                ->where('users.id',Auth::user()->id)
                ->first();

                return Response::json([
                    'response' => true,
                    'user' => $user,
                    'token' => $token
                ]);

            }
            
        }else{
            
            return Response::json([
                'response' => false,
                'message' => 'Por favor verifique su usuario o contraseña y vuelva a intentarlo, si tiene problemas llámenos a la línea de soporte.'
            ]);
        }
    }
    
});