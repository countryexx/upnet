<?php

namespace App\Console;

use Illuminate\Support\Facades\DB;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\NotificacionesUpnet;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        /*Listado de notificaciones programadas de forma periódica*/

        //Notificacion a los empleados sobre el cumpleaños de algun trabajador el día siguiente; ejemplo: Mañana es el cumpleaños de Fulanito (Diario)
        //Notificación a los empleados sobre el cumpleaños el día actual; ejemplo: Hoy es el cumpleaños de Fulanito (Diario)
        //Notificación a los proveedores sobre documentos vencidos y por vencerse (Diario)
        //Notificación a los empleados sobre tareas con poco plazo con respecto al día actual (Diario)
        //Notificación a conductores de servicios próximos a iniciar (Reconfirmaciones) (Diario/Cada 5 minutos)
        //Notificación a clientes sobre servicios próximos a iniciar (Reconfirmaciones) (Diario/Cada 5 minutos)
        //Notificación a Operaciones sobre Usuarios de rutas dobles (Diario)
        //Notificación a contabilidad sobre facturas vencidas que no tienen ingreso (Diario)
        //Notificación a Mantenimeinto sobre documentación pendiente que no ha sido aprobada a la fecha (Diario)

        /*$schedule->call(function () {
            
            $number = 3013869946;
            $nombre = 'DAVID COBA';
            $dia = 'MAÑANA';
            $hora = '21:00';
            $placa = 'UUW126';
            $conductor = 'SAMUEL GONZÁLEZ opc 2';
            $numero = 3013869946;
            $qr = '3013869946';

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v15.0/109529185312847/messages");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);

            curl_setopt($ch, CURLOPT_POST, TRUE);

            curl_setopt($ch, CURLOPT_POSTFIELDS, "{
            \"messaging_product\": \"whatsapp\",
            \"to\": \"".$number."\",
            \"type\": \"template\",
            \"template\": {
                \"name\": \"ruta_qr\",
                \"language\": {
                \"code\": \"es\",
                },
                \"components\": [{
                \"type\": \"body\",
                \"parameters\": [{
                    \"type\": \"text\",
                    \"text\": \"".$nombre."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$dia."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$hora."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$placa."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$conductor."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$numero."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"3013869946\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"3013869946\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"3013869946\",
                }]
                },
                {
                \"type\": \"button\",
                \"sub_type\": \"url\",
                \"index\": \"0\",
                \"parameters\": [{
                    \"type\": \"payload\",
                    \"payload\": \"".$qr."\"
                }]
                }]
            }
            }");

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer EAAHPlqcJlZCMBAMDLjgTat7TlxvpmDq1fgzt2gZBPUnEsTyEuxuJw9uvGJM1WrWtpN7fmpmn3G2KXFZBRIGLKEDhZBPZAeyUSy2OYiIcNEf2mQuFcW67sgGoU95VkYayreD5iBx2GbnZBgaGvS8shX6f2JKeBp7pm9TNLm2EZBEbcx0Sdg47miONZCpUNZCfqEWlZAFxkltEOBPAZDZD"
            ));

            $response = curl_exec($ch);
            curl_close($ch);

        })->everyTwoMinutes();*/

        /*$schedule->call(function () {

            $number = 3013869946;
            $nombre = 'DAVID';
            $dia = 'HOY';
            $hora = '19:00';
            $placa = 'UUW126';
            $conductor = 'SAMUEL GONZÁLEZ';
            $numero = 3013869946;
            $qr = 'dfdad4545dsfsdfs';

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, "https://graph.facebook.com/v15.0/109529185312847/messages");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);

            curl_setopt($ch, CURLOPT_POST, TRUE);

            curl_setopt($ch, CURLOPT_POSTFIELDS, "{
            \"messaging_product\": \"whatsapp\",
            \"to\": \"".$number."\",
            \"type\": \"template\",
            \"template\": {
                \"name\": \"ruta_qr\",
                \"language\": {
                \"code\": \"es\",
                },
                \"components\": [{
                \"type\": \"body\",
                \"parameters\": [{
                    \"type\": \"text\",
                    \"text\": \"".$nombre."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$dia."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$hora."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$placa."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$conductor."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"".$numero."\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"3147484288\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"3012030290\",
                },
                {
                    \"type\": \"text\",
                    \"text\": \"3014791279\",
                }]
                },
                {
                \"type\": \"button\",
                \"sub_type\": \"url\",
                \"index\": \"0\",
                \"parameters\": [{
                    \"type\": \"payload\",
                    \"payload\": \"".$qr."\"
                }]
                }]
            }
            }");

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer EAAHPlqcJlZCMBAMDLjgTat7TlxvpmDq1fgzt2gZBPUnEsTyEuxuJw9uvGJM1WrWtpN7fmpmn3G2KXFZBRIGLKEDhZBPZAeyUSy2OYiIcNEf2mQuFcW67sgGoU95VkYayreD5iBx2GbnZBgaGvS8shX6f2JKeBp7pm9TNLm2EZBEbcx0Sdg47miONZCpUNZCfqEWlZAFxkltEOBPAZDZD"
            ));

            $response = curl_exec($ch);
            curl_close($ch);
            
        })->everyMinute();*/

        //Función para notificar sobre el cumpleaños de los empleados
        /*$schedule->call(function () {

            $mes = date('m');
            $dia = date('d');
            $querys = $mes.$dia;

            $consulta = DB::table('empleados')->where('cumpleanos',$querys)->where('estado',1)->get();

            //SI EN EL DIA Y MES ACTUAL HAY UNO O MÁS CUMPLIMENTADOS
            if($consulta!=null){
                $valores = '';
                foreach ($consulta as $employ) {

                    $fecha = explode('-', $employ->fecha_nacimiento);
                    $mes = $fecha[1];
                    if($mes==='01'){
                        $mes = 'ENERO';
                    }else if($mes==='02'){
                        $mes = 'FEBRERO';
                    }else if($mes==='03'){
                        $mes = 'MARZO';
                    }else if($mes==='04'){
                        $mes = 'ABRIL';
                    }else if($mes==='05'){
                        $mes = 'MAYO';
                    }else if($mes==='06'){
                        $mes = 'JUNIO';
                    }else if($mes==='07'){
                        $mes = 'JULIO';
                    }else if($mes==='08'){
                        $mes = 'AGOSTO';
                    }else if($mes==='09'){
                        $mes = 'SEPTIEMBRE';
                    }else if($mes==='10'){
                        $mes = 'OCTUBRE';
                    }else if($mes==='11'){
                        $mes = 'NOVIEMBRE';
                    }else if($mes==='12'){
                        $mes = 'DICIEMBRE';
                    }

                    if($valores!=null){
                        $valores .= ', <br> '.$employ->nombres.' '.$employ->apellidos.'';
                    }else{
                        $valores .=$employ->nombres.' '.$employ->apellidos.'';
                    }

                }
                
                $frase = 'AOTOUR Felicita a :';

                $datos = $frase.' <p style="color: gray"> '.$valores.'  &#x270b;</p> Por la celebración de su cumpleaños hoy '.$day.' '.$fecha[2].' de '.$mes.' del 2024. &#x1f973; &#x1f389;';
            }elseif($welcome->mensaje!=null){
                $datos = $welcome->mensaje;
            }else{
                $datos = null;
            }

            
        })->everyMinute();*/

        //Función para el envío de cumpleaños (un día antes)
        /*$schedule->call(function () {
            
            $fecha = date('Y-m-d');

            $diasiguiente = strtotime ('+1 day', strtotime($fecha));
            $diasiguiente = date('Y-m-d' , $diasiguiente);

            $cumpleaneros = DB::table('empleados')
            ->where('fecha_nacimiento',$diasiguiente)
            ->first();

            foreach ($cumpleaneros as $cumple) {
                //$numero = DB::table()
            }
            
        })->daily();*/

        //Función para el envío de notificaciones a los USUARIOS - TAREAS PENDIENTES
        /*$schedule->call(function () {
            
            $fecha = date('Y-m-d');

            $diasiguiente = strtotime ('+1 day', strtotime($fecha));
            $diasiguiente = date('Y-m-d' , $diasiguiente);

            $users = "select users.*, tipo_usuario.codigo from users left join tipo_usuario on tipo_usuario.id = users.id_perfil where users.fk_tipo_usuario = 1 and users.master != 1 and users.baneado is null";
            $users = DB::select($users);

            foreach ($users as $user) {

                $cantidadTareas = "select id from proyectos where fk_responsable = ".$user->id." and fk_estado in(3,4,5)";
                $cantidadTareas = DB::select($cantidadTareas);
                $cont = count($cantidadTareas);

                if($cont>0) {

                    $asunto = 'Tus tareas pendientes...';
                    $cuerpo = 'Tienes '.$cont.' tareas en proceso ';
                    $usuario = $user->id;

                    $notificacion = new NotificacionesUpnet;
                    $notificacion->asunto = $asunto;
                    $notificacion->cuerpo = $cuerpo;
                    $notificacion->fk_users = $usuario;
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
            
        })->everyMinute();*/
        //})->mondays();

        //Función para el envío de notificaciones a los ADMINISTRADORES - TAREAS POR APROBAR
        /*$schedule->call(function () {

            $fecha = date('Y-m-d');

            $diasiguiente = strtotime ('+1 day', strtotime($fecha));
            $diasiguiente = date('Y-m-d' , $diasiguiente);

            $users = "select * FROM users WHERE master = 1 and users.baneado is null";
            $users = DB::select($users);

            foreach ($users as $user) {

                $cantidadTareas = "select id from proyectos where fk_estado = 9";
                $cantidadTareas = DB::select($cantidadTareas);
                $cont = count($cantidadTareas);

                if($cont>0) {

                    $asunto = 'Tus tareas por aprobar...';
                    $cuerpo = 'Tienes '.$cont.' tareas que no has aprobado.';
                    $usuario = $user->id;

                    $notificacion = new NotificacionesUpnet;
                    $notificacion->asunto = $asunto;
                    $notificacion->cuerpo = $cuerpo;
                    $notificacion->fk_users = $usuario;
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

        })->everyMinute();*/
        //})->mondays();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        
        require base_path('routes/console.php');
    }
}
