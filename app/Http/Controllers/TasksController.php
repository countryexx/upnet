<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Rol;
use App\Models\Entidad;
use App\Models\UserEntidad;
use App\Models\Grupo;
use App\Models\Proyecto;
use App\Models\SubProyecto;
use App\Models\EvidenciasProyecto;
use App\Models\EvidenciasSubProyecto;
use App\Models\NotificacionesUpnet;
use Auth;
use Response;
Use DB;
Use Config;
use Hash;

class TasksController extends Controller
{
    public function statuslist(Request $request) {

        $estados = DB::table('estados')
        ->join('estados_maestros', 'estados_maestros.id', '=', 'estados.fk_estados_maestros')
        ->select('estados.*', 'estados_maestros.codigo as codigo_estados_maestros', 'estados_maestros.nombre as nombre_estados_maestros')
        ->where('estados_maestros.codigo',$request->codigo)
        ->where('estados.activo',1)
        ->get();

        return Response::json([
            'response' => true,
            'estados' => $estados
        ]);

    }

    public function listresponsible(Request $request) {

        $responsable = DB::table('users')
        ->join('tipo_usuario', 'tipo_usuario.id', '=', 'users.fk_tipo_usuario')
        ->select('users.*', 'tipo_usuario.codigo')
        ->where('tipo_usuario.codigo','EMPL')
        ->whereNull('users.baneado')
        ->get();

        return Response::json([
            'response' => true,
            'responsable' => $responsable
        ]);

    }

    public function creategroup(Request $request) {

        $know = "select * from grupos order by orden asc";
        $mysql = DB::select($know);
        $total = count($mysql);

        $grupo = new Grupo;
        $grupo->nombre_grupo = $request->nombre_grupo;
        $grupo->fk_creado_por_users = $request->user_id;
        $grupo->orden = intval($total)+1;
        $grupo->save();

        /*$update = DB::table('grupos')
        ->where('id',$grupo->id)
        ->update([
            'orden' => intval($grupo->id)
        ]);*/

        return Response::json([
            'response' => true,
            'total' => $total
        ]);

    }
    //Customisar para consultar por usuario o hacer una adicional para listar por usuario
    public function listgroups(Request $request) {

        $grupos = DB::table('grupos')
        ->where('activo',1)
        ->get();

        return Response::json([
            'response' => true,
            'grupos' => $grupos
        ]);

    }

    public function createproject(Request $request) {

        $know = "select * from proyectos order by orden asc";
        $mysql = DB::select($know);
        $total = count($mysql);

        $proyecto = new Proyecto;
        $proyecto->fk_responsable = $request->fk_responsable;
        $proyecto->fecha_inicial = $request->fecha_inicial;
        $proyecto->fecha_final = $request->fecha_final;
        $proyecto->proyecto = $request->proyecto;
        $proyecto->fk_prioridad = $request->fk_prioridad;
        $proyecto->fk_estado = $request->fk_estado;
        $proyecto->nota = $request->nota;
        $proyecto->orden = intval($total)+1;
        $proyecto->fk_asignado_por = $request->fk_asignado_por;
        $proyecto->fk_grupos = $request->fk_grupos;
        
        if($proyecto->save()){
            
            if($request->fk_asignado_por!=$request->fk_responsable) {

                $asignador = Auth::user()->first_name;
                $tarea = $request->proyecto;
                $asunto = 'Tarea Asignada';
                $cuerpo = ''.strtoupper($asignador).' te ha asignado la tarea '.strtoupper($tarea).'';

                $notificacion = new NotificacionesUpnet;
                $notificacion->asunto = $asunto;
                $notificacion->cuerpo = $cuerpo;
                $notificacion->fk_users = $request->fk_responsable;//ok
                $notificacion->estado = 11;
                $notificacion->save();

                $idpusher = "578229";
                $keypusher = "a8962410987941f477a1";
                $secretpusher = "6a73b30cfd22bc7ac574";

                $channel = 'notificaciones_'.$request->fk_responsable;
                $name = 'not'.$request->fk_responsable;

                $data = json_encode([
                    'asunto' => $asunto,
                    'cuerpo' => $cuerpo,
                ]);

                $app_id = $idpusher;
                $key = $keypusher;
                $secret = $secretpusher;

                $body = [
                    'data' => $data,
                    'name' => $name,
                    'channel' => $channel
                ];

                $auth_timestamp =  strtotime('now');
                //$auth_timestamp = '1534427844';

                $auth_version = '1.0';

                //Body convertido a md5 mediante una funcion
                $body_md5 = md5(json_encode($body));

                $string_to_sign =
                "POST\n/apps/".$app_id.
                "/events\nauth_key=".$key.
                "&auth_timestamp=".$auth_timestamp.
                "&auth_version=".$auth_version.
                "&body_md5=".$body_md5;

                $auth_signature = hash_hmac('SHA256', $string_to_sign, $secret);

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://api-us2.pusher.com/apps/'.$app_id.'/events?auth_key='.$key.'&body_md5='.$body_md5.'&auth_version=1.0&auth_timestamp='.$auth_timestamp.'&auth_signature='.$auth_signature.'&');

                curl_setopt($ch, CURLOPT_POST, true);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                $headers = [
                'Content-Type: application/json'
                ];

                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

                $response = curl_exec($ch);
                //Envío de pusher

            }

            return Response::json([
                'response' => true
            ]);
        }

    }
    //Customisar para consultar por usuario o hacer una adicional para listar por usuario
    public function listprojects(Request $request) {

        $proyectos = DB::table('proyectos')
        ->leftjoin('users', 'users.id', '=', 'proyectos.fk_responsable')
        //->leftjoin('estados', 'estados.id', '=', 'proyectos.fk_estado')
        ->select('proyectos.*', 'users.first_name', 'users.last_name')
        ->get();

        return Response::json([
            'response' => true,
            'proyectos' => $proyectos
        ]);

    }

    public function listprojectsuser(Request $request) {

        $proyectos = DB::table('proyectos')
        ->leftjoin('users', 'users.id', '=', 'proyectos.fk_responsable')
        ->select('proyectos.*', 'users.first_name', 'users.last_name')
        ->where('proyectos.fk_responsable',Auth::user()->id)
        ->get();

        return Response::json([
            'response' => true,
            'proyectos' => $proyectos
        ]);

    }

    public function createsubproject(Request $request) {

        $know = "select * from sub_proyectos order by orden asc";
        $mysql = DB::select($know);
        $total = count($mysql);

        $proyecto = new SubProyecto;
        $proyecto->fk_responsable = $request->fk_responsable;
        $proyecto->fecha_inicial = $request->fecha_inicial;
        $proyecto->fecha_final = $request->fecha_final;
        $proyecto->proyecto = $request->proyecto;
        $proyecto->fk_prioridad = $request->fk_prioridad;
        $proyecto->fk_estado = $request->fk_estado;
        $proyecto->nota = $request->nota;
        $proyecto->orden = intval($total)+1;
        $proyecto->fk_asignado_por = $request->fk_asignado_por;
        $proyecto->fk_proyectos = $request->fk_proyectos;
        $proyecto->save();
        
        return Response::json([
            'response' => true
        ]);

    }
    //Customisar para consultar por usuario o hacer una adicional para listar por usuario
    public function listsubprojects(Request $request) {

        $sub_proyectos = DB::table('sub_proyectos')
        ->leftjoin('users', 'users.id', '=', 'sub_proyectos.fk_responsable')
        ->select('sub_proyectos.*', 'users.first_name', 'users.last_name')
        ->get();

        return Response::json([
            'response' => true,
            'sub_proyectos' => $sub_proyectos
        ]);

    }

    /*Evidencias Proyectos */
    public function createevidenceproject(Request $request) {

        $evidencia = new EvidenciasProyecto;
        $evidencia->tipo_archivo = $request->tipo_archivo;
        $evidencia->fk_usuario_subida = $request->fk_usuario_subida;
        $evidencia->url_archivo = 'evidencia';
        $evidencia->fecha_subida = $request->fecha_subida;
        $evidencia->fk_proyecto = $request->fk_proyecto;
        $evidencia->save();

        if($request->hasFile('archivo')){

            $file = $request->file('archivo');
            $name_file = str_replace(' ', '', $file->getClientOriginalName());
            
            $numero = $evidencia->id;

            $ubicacion_pdf = 'images/soportes_tareas/';
            $file->move($ubicacion_pdf, $numero.$name_file);
            $ubicacion_archivo = $numero.$ubicacion_pdf;

            $update = DB::table('evidencias_proyecto')
            ->where('id',$evidencia->id)
            ->update([
                'url_archivo' => $numero.$name_file
            ]);
  
        }else{
            $ubicacion_archivo = null;
        }

        return Response::json([
            'response' => true
        ]);

    }

    public function listevidenceproject(Request $request) {

        $evidencias_proyecto = DB::table('evidencias_proyecto')
        ->where('fk_proyecto', $request->project_id)
        ->get();

        return Response::json([
            'response' => true,
            'evidencias_proyecto' => $evidencias_proyecto
        ]);

    }

    /*Evidencias Sub Proyectos */
    public function createevidencesubproject(Request $request) {

        $evidencia = new EvidenciasSubProyecto;
        $evidencia->tipo_archivo = $request->tipo_archivo;
        $evidencia->fk_usuario_subida = $request->fk_usuario_subida;
        $evidencia->url_archivo = $request->url_archivo;
        $evidencia->fecha_subida = $request->fecha_subida;
        $evidencia->fk_sub_proyecto = $request->fk_sub_proyecto;
        $evidencia->save();

        //Guardar archivo en el servidor - PENDING

        return Response::json([
            'response' => true
        ]);

    }

    public function listevidencesubproject(Request $request) {

        $evidencias_sub_proyecto = DB::table('sub_evidencias_proyecto')
        ->where('fk_sub_proyecto', $request->project_id)
        ->get();

        return Response::json([
            'response' => true,
            'evidencias_sub_proyecto' => $evidencias_sub_proyecto
        ]);

    }

    public function editpriority(Request $request) {

        $project_id = $request->project_id;
        $new_priority = $request->new_priority;

        $update = DB::table('proyectos')
        ->where('id',$project_id)
        ->update([
            'fk_prioridad' => $new_priority
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function editprioritysub(Request $request) {

        $sub_project_id = $request->sub_project_id;
        $new_priority = $request->new_priority;

        $update = DB::table('sub_proyectos')
        ->where('id',$sub_project_id)
        ->update([
            'fk_prioridad' => $new_priority
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function editstatus(Request $request) {

        $project_id = $request->project_id;
        $new_status = $request->new_status;
        $old_status = $request->old_status;

        if($new_status==9 or $new_status==2 or $new_status==3) {
            
            $project = DB::table('proyectos')->where('id',$project_id)->first();

            if($new_status==9 and Auth::user()->master!=1){

                $masters = DB::table('users')
                ->where('master',1)
                ->get();

                foreach ($masters as $user) {
                    
                    $responsable = DB::table('users')
                    ->where('id',$project->fk_responsable)
                    ->first();

                    $asunto = 'Tarea Pendiente por Aprobar';
                    $cuerpo = 'Tienes una nueva tarea finalizada de '.$responsable->first_name.' pendiente por aprobar';
                    $usuario = $user->id;

                    $notificacion = new NotificacionesUpnet;
                    $notificacion->asunto = $asunto;
                    $notificacion->cuerpo = $cuerpo;
                    $notificacion->fk_users = $usuario;
                    $notificacion->estado = $request->new_status;
                    $notificacion->save();

                    $idpusher = "578229";
                    $keypusher = "a8962410987941f477a1";
                    $secretpusher = "6a73b30cfd22bc7ac574";
                    $channel = 'notificaciones_'.$usuario;
                    $name = 'not'.$usuario;
                    $data = json_encode([
                        'asunto' => $asunto,
                        'cuerpo' => $cuerpo,
                    ]);
                    $app_id = $idpusher;
                    $key = $keypusher;
                    $secret = $secretpusher;
                    $body = [
                        'data' => $data,
                        'name' => $name,
                        'channel' => $channel
                    ];
                    $auth_timestamp =  strtotime('now');
                    $auth_version = '1.0';
                    $body_md5 = md5(json_encode($body));

                    $string_to_sign =
                    "POST\n/apps/".$app_id.
                    "/events\nauth_key=".$key.
                    "&auth_timestamp=".$auth_timestamp.
                    "&auth_version=".$auth_version.
                    "&body_md5=".$body_md5;

                    $auth_signature = hash_hmac('SHA256', $string_to_sign, $secret);

                    $ch = curl_init();

                    curl_setopt($ch, CURLOPT_URL, 'https://api-us2.pusher.com/apps/'.$app_id.'/events?auth_key='.$key.'&body_md5='.$body_md5.'&auth_version=1.0&auth_timestamp='.$auth_timestamp.'&auth_signature='.$auth_signature.'&');
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    $headers = [
                        'Content-Type: application/json'
                    ];
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                    $response = curl_exec($ch);
                    
                }

            }else if($new_status==2){

                $aceptador = Auth::user()->first_name.' '.Auth::user()->last_name;
                $tarea = $project->proyecto;
                $asunto = 'Tarea Aprobada';
                $cuerpo = 'Tu tarea '.$tarea.' fue aprobada por '.$aceptador;
                $usuario = $project->fk_responsable;

                $notificacion = new NotificacionesUpnet;
                $notificacion->asunto = $asunto;
                $notificacion->cuerpo = $cuerpo;
                $notificacion->fk_users = $usuario;//ok
                $notificacion->estado = 11;
                $notificacion->save();

                $idpusher = "578229";
                $keypusher = "a8962410987941f477a1";
                $secretpusher = "6a73b30cfd22bc7ac574";
                $channel = 'notificaciones_'.$usuario;
                $name = 'not'.$usuario;
                $data = json_encode([
                    'asunto' => $asunto,
                    'cuerpo' => $cuerpo,
                ]);
                $app_id = $idpusher;
                $key = $keypusher;
                $secret = $secretpusher;
                $body = [
                    'data' => $data,
                    'name' => $name,
                    'channel' => $channel
                ];
                $auth_timestamp =  strtotime('now');
                $auth_version = '1.0';
                $body_md5 = md5(json_encode($body));

                $string_to_sign =
                "POST\n/apps/".$app_id.
                "/events\nauth_key=".$key.
                "&auth_timestamp=".$auth_timestamp.
                "&auth_version=".$auth_version.
                "&body_md5=".$body_md5;

                $auth_signature = hash_hmac('SHA256', $string_to_sign, $secret);

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://api-us2.pusher.com/apps/'.$app_id.'/events?auth_key='.$key.'&body_md5='.$body_md5.'&auth_version=1.0&auth_timestamp='.$auth_timestamp.'&auth_signature='.$auth_signature.'&');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $headers = [
                    'Content-Type: application/json'
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                $response = curl_exec($ch);

            }else if($new_status==3 and $old_status!=5){

                $aceptador = Auth::user()->first_name.' '.Auth::user()->last_name;
                $tarea = $project->proyecto;
                $asunto = 'Tarea no Aprobada';
                $cuerpo = 'Tu tarea '.$tarea.' no fue aprobada por '.$aceptador;
                $usuario = $project->fk_responsable;

                $notificacion = new NotificacionesUpnet;
                $notificacion->asunto = $asunto;
                $notificacion->cuerpo = $cuerpo;
                $notificacion->fk_users = $usuario;//ok
                $notificacion->estado = 11;
                $notificacion->save();

                $idpusher = "578229";
                $keypusher = "a8962410987941f477a1";
                $secretpusher = "6a73b30cfd22bc7ac574";
                $channel = 'notificaciones_'.$usuario;
                $name = 'not'.$usuario;
                $data = json_encode([
                    'asunto' => $asunto,
                    'cuerpo' => $cuerpo,
                ]);
                $app_id = $idpusher;
                $key = $keypusher;
                $secret = $secretpusher;
                $body = [
                    'data' => $data,
                    'name' => $name,
                    'channel' => $channel
                ];
                $auth_timestamp =  strtotime('now');
                $auth_version = '1.0';
                $body_md5 = md5(json_encode($body));

                $string_to_sign =
                "POST\n/apps/".$app_id.
                "/events\nauth_key=".$key.
                "&auth_timestamp=".$auth_timestamp.
                "&auth_version=".$auth_version.
                "&body_md5=".$body_md5;

                $auth_signature = hash_hmac('SHA256', $string_to_sign, $secret);

                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://api-us2.pusher.com/apps/'.$app_id.'/events?auth_key='.$key.'&body_md5='.$body_md5.'&auth_version=1.0&auth_timestamp='.$auth_timestamp.'&auth_signature='.$auth_signature.'&');
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                $headers = [
                    'Content-Type: application/json'
                ];
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
                $response = curl_exec($ch);

            }

        }

        $update = DB::table('proyectos')
        ->where('id',$project_id)
        ->update([
            'fk_estado' => $new_status
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function editstatussub(Request $request) {

        $sub_project_id = $request->sub_project_id;
        $new_status = $request->new_status;

        $update = DB::table('sub_proyectos')
        ->where('id',$sub_project_id)
        ->update([
            'fk_estado' => $new_status
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function editresponsible(Request $request) {

        $project_id = $request->project_id;
        $responsible_new = $request->responsible_new;
        $update = DB::table('proyectos')
        ->where('id',$project_id)
        ->update([
            'fk_responsable' => $responsible_new
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function editresponsiblesub(Request $request) {

        $sub_project_id = $request->sub_project_id;
        $responsible_new = $request->responsible_new;
        $update = DB::table('sub_proyectos')
        ->where('id',$sub_project_id)
        ->update([
            'fk_responsable' => $responsible_new
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function editordengroup(Request $request) {

        $group_id = $request->group_id;
        $orden = DB::table('grupos')->where('id',$group_id)->first();

        $new_position = $request->new_position;

        if($orden->orden<$new_position) { //El orden actual es menor al nuevo
            $consulta = "select id, orden from grupos where orden <= ".$new_position." and orden != ".$orden->orden." order by orden desc";
        }else{
            $consulta = "select id, orden from grupos where orden >= ".$new_position." and orden != ".$orden->orden." order by orden asc";
        }

        $consulta = DB::select($consulta);

        $iterador = intval($new_position);

        foreach ($consulta as $key) {
            
            if($orden->orden<$new_position) { //El orden actual es menor al nuevo
                $iterador--;
            }else{
                $iterador++;
            }

            $update = DB::table('grupos')
            ->where('id',$key->id)
            ->update([
                'orden' => $iterador
            ]);
        }

        $update2 = DB::table('grupos')
        ->where('id',$group_id)
        ->update([
            'orden' => $new_position
        ]);

        return Response::json([
            'response' => true,
            'consulta' => $consulta
        ]);

    }

    public function editordenproject(Request $request) {

        $project_id = $request->project_id;
        $orden = DB::table('proyectos')->where('id',$project_id)->first();

        $new_position = $request->new_position;

        if($orden->orden<$new_position) { //El orden actual es menor al nuevo
            $consulta = "select id, orden from proyectos where orden <= ".$new_position." and orden != ".$orden->orden." order by orden desc";
        }else{
            $consulta = "select id, orden from proyectos where orden >= ".$new_position." and orden != ".$orden->orden."";
        }

        $consulta = DB::select($consulta);

        $iterador = intval($new_position);

        foreach ($consulta as $key) {
            
            if($orden->orden<$new_position) { //El orden actual es menor al nuevo
                $iterador--;
            }else{
                $iterador++;
            }

            $update = DB::table('proyectos')
            ->where('id',$key->id)
            ->update([
                'orden' => $iterador
            ]);
        }

        $update2 = DB::table('proyectos')
        ->where('id',$project_id)
        ->update([
            'orden' => $new_position
        ]);

        return Response::json([
            'response' => true,
            'consulta' => $consulta
        ]);

    }

    public function editordensubproject(Request $request) {

        $sub_project_id = $request->sub_project_id;
        $orden = DB::table('sub_proyectos')->where('id',$sub_project_id)->first();

        $new_position = $request->new_position;

        if($orden->orden<$new_position) {
            $consulta = "select id, orden from sub_proyectos where orden <= ".$new_position." and orden != ".$orden->orden." order by orden desc";
        }else{
            $consulta = "select id, orden from sub_proyectos where orden >= ".$new_position." and orden != ".$orden->orden."";
        }

        $consulta = DB::select($consulta);

        $iterador = intval($new_position);

        foreach ($consulta as $key) {
            
            if($orden->orden<$new_position) {
                $iterador--;
            }else{
                $iterador++;
            }

            $update = DB::table('sub_proyectos')
            ->where('id',$key->id)
            ->update([
                'orden' => $iterador
            ]);
        }

        $update2 = DB::table('sub_proyectos')
        ->where('id',$sub_project_id)
        ->update([
            'orden' => $new_position
        ]);

        return Response::json([
            'response' => true,
            'consulta' => $consulta
        ]);

    }

    public function createnotification(Request $request) {

        $notificacion = new NotificacionesUpnet;
        $notificacion->asunto = $request->asunto;
        $notificacion->cuerpo = $request->cuerpo;
        $notificacion->fk_users = $request->fk_users;
        $notificacion->estado = $request->estado;
        $notificacion->save();

        return Response::json([
            'response' => true
        ]);

    }

    public function readnotification(Request $request) {
        
        $notification = DB::table('notificaciones_upnet')
        ->where('id',$request->notification_id)
        ->update([
            'estado' => $request->estado
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function readnotifications(Request $request) {

        $notification = DB::table('notificaciones_upnet')
        ->where('fk_users',Auth::user()->id)
        ->where('estado',11)
        ->update([
            'estado' => 10
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function listnotifications(Request $request) {

        $notificaciones = DB::table('notificaciones_upnet')
        ->where('fk_users',$request->user_id)
        ->get();

        return Response::json([
            'response' => true,
            'notifications' => $notificaciones
        ]);

    }

    public function deletenotification(Request $request) {

        $notification_id = $request->notification_id;

        $delete = DB::table('notificaciones_upnet')
        ->where('id',$notification_id)
        ->update([
            'estado_eliminacion' => 1
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function deletenotifications(Request $request) {

        $notification = DB::table('notificaciones_upnet')
        ->where('fk_users',Auth::user()->id)
        ->whereNull('estado_eliminacion')
        ->update([
            'estado_eliminacion' => 1
        ]);

        return Response::json([
            'response' => true
        ]);

    }

    public function push(Request $request) {
        
        //Prueba de Pusher
        $idpusher = "578229";
        $keypusher = "a8962410987941f477a1";
        $secretpusher = "6a73b30cfd22bc7ac574";

        //CANAL DE NOTIFICACIÓN DE RECONFIRMACIONES
        $channel = 'notificaciones_2';
        $name = 'not2';

        $data = json_encode([
          'mensaje' => 'Betty luz carrillo te ha asignado la tarea COMPRAR IPAD',
        ]);

        $app_id = $idpusher;
        $key = $keypusher;
        $secret = $secretpusher;

        $body = [
        'data' => $data,
        'name' => $name,
        'channel' => $channel
        ];

        $auth_timestamp =  strtotime('now');
        //$auth_timestamp = '1534427844';

        $auth_version = '1.0';

        //Body convertido a md5 mediante una funcion
        $body_md5 = md5(json_encode($body));

        $string_to_sign =
        "POST\n/apps/".$app_id.
        "/events\nauth_key=".$key.
        "&auth_timestamp=".$auth_timestamp.
        "&auth_version=".$auth_version.
        "&body_md5=".$body_md5;

        $auth_signature = hash_hmac('SHA256', $string_to_sign, $secret);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://api-us2.pusher.com/apps/'.$app_id.'/events?auth_key='.$key.'&body_md5='.$body_md5.'&auth_version=1.0&auth_timestamp='.$auth_timestamp.'&auth_signature='.$auth_signature.'&');

        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $headers = [
        'Content-Type: application/json'
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));

        $response = curl_exec($ch);

        return Response::json([
            'response' => true,
            'pusher' => $response
        ]);

    }

}
