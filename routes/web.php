<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use App\Models\Rol;
use App\Models\Proveedor;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    

    if (!Auth::check()){

        return view('admin.login');

    }else{

        $id_rol = Auth::user()->id_rol;
        $id_usuario = Auth::user()->id;
        $userportal = Auth::user()->usuario_portal;
        $permisos = DB::table('roles')->where('id',$id_rol)->first();
        $permisos = json_decode($permisos->permisos);

        $welcome = DB::table('welcome')->orderBy('id','desc')->limit(1)->first();

        return View::make('admin.principal')
        ->with([
            'permisos'=>$permisos,
            'userportal'=>$userportal,
            'idusuario' =>$id_usuario,
            'welcome' =>$welcome
        ]);

    }

});

Route::post('/autenticate', function (Request $request) {

    $messages = [
        "username.required" => "El Usuario es requerido",
        "username.exists" => "El Usuario no existe",
        "password.required" => "La contraseña requerida",
    ];
    
    $validator = Validator::make($request->all(), [
        'username' => 'required|exists:users',
        'password' => 'required'
    ], $messages);

    if ($validator->fails()) {
        
        if( count($validator->messages())>1 ) {
            return Response::json([
                'respuesta' => 'requeridos',
                'validator' => $validator->messages(),
                'count' => count($validator->messages())
            ]);
        }else{
            return Response::json([
                'respuesta' => 'requeridos2',
                'validator' => $validator->messages(),
                'count' => count($validator->messages())
            ]);
        }

    } else {
        
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
    
            $user = Auth::user();
            Auth::logoutOtherDevices($request->password);
            
            $update = DB::table('users')
            ->where('id',$user->id)
            ->update([
                'last_login' => date('Y-m-d H:i')
            ]);

            if (Auth::check()) {
                $sw = 'logueado';
            }else{
                $sw = 'no logueado';
            }
    
            return Response::json([
                'respuesta' => true,
                'user' => $user
            ]);
        }else{
            
            return Response::json([
                'respuesta' => 'incorrecta'
            ]);
        }

    }
    
});

Route::get('logout', function(Request $request) {

    Auth::logout();
    
    $request->session()->invalidate();
    
    $request->session()->regenerateToken();
    
    return Redirect::to('/')->with('mensaje','Ud ha sido deslogueado');

});

Route::get('usuarios', function() {

    if (Auth::check()) {
      $id_rol = Auth::user()->id_rol;
      $userportal = Auth::user()->usuario_portal;
      $permisos = DB::table('roles')->where('id',$id_rol)->first();
      $permisos = json_decode($permisos->permisos);
    }else{
      $id_rol = null;
      $permisos = null;
      $permisos = null;
    }

    if (isset($permisos->administracion->usuarios->ver)){
        $ver = $permisos->administracion->usuarios->ver;
    }else{
        $ver = null;
    }

    if (!Auth::check()){

        return Redirect::to('/')->with('mensaje','<i class="fa fa-exclamation-triangle"></i> Para ingresar al sistema debe loguearse primero');

    }else if($ver!='on' ) {
        return View::make('admin.permisos');
    }else {

        $roles = DB::table('roles')->get();
        $usuarios = DB::table('users')->whereNull('usuario_app')->where('usuario_portal',0)->orderBy('username')->get();

        return View::make('usuarios.listado', [
            'usuarios' => $usuarios,
            'roles' => $roles,
            'permisos' => $permisos,
            'userportal' => $userportal
        ]);
    }
    
});

Route::post('verroles', function() {

    //if (!Auth::check())
    //{
      //  return Redirect::to('/')->with('mensaje','<i class="fa fa-exclamation-triangle"></i> Para ingresar al sistema debe loguearse primero');

    //}else {

        //if (request::ajax()) {

            $roles = DB::table('roles')
                ->select('roles.id','roles.nombre_rol','users.first_name','users.last_name','roles.created_at')
                ->leftJoin('users','roles.creado_por','=','users.id')
                ->get();

            if ($roles!=null){

                return Response::json([
                    'respuesta'=>true,
                    'roles'=>$roles
                ]);

            }
        //}
    //}

});

Route::post('permisosrol', function(Request $request) {

    if (!Auth::check())
    {
        return Redirect::to('/')->with('mensaje','<i class="fa fa-exclamation-triangle"></i> Para ingresar al sistema debe loguearse primero');

    }else {

        //if (Request::ajax()) {

            $id = $request->id;
            //$id = Input::get('id');
            $rol = DB::table('roles')->where('id',$id)->first();

            return Response::json([
                'respuesta'=>true,
                'permisos'=>$rol->permisos,
                'nombre_rol'=>$rol->nombre_rol
            ]);

        //}

    }
});

Route::post('crearusuario', function(Request $request) {

    if (!Auth::check()){

        return Response::json([
            'respuesta'=>'relogin'
        ]);

    }else{

        try{
            Config::set('cartalyst/auth::users.login_attribute', 'username');

            //ULTIMO REGISTRO DE USUARIO PARA HACER EL CALCULO DEL CONSECUTIVO
            $consulta = "select * from users where usuario_portal = 0 order by id desc limit 1";
            $ultimo = DB::select($consulta);

            $numero = intval(str_replace('AO','',$ultimo[0]->username))+1;

            $usuario = new User;
            $usuario->username = 'AO'.$numero;
            $usuario->password = Hash::make($request->contrasena);
            $usuario->activated = true;
            $usuario->first_name = $request->nombres;
            $usuario->last_name = $request->apellidos;
            $usuario->tipo_usuario = 1;
            $usuario->id_rol = $request->rol;
            $usuario->localidad =  $request->localidad;
            $usuario->save();

            if ($usuario) {
              return Response::json([
                'respuesta'=>true
              ]);
            }else{
                return Response::json([
                    'respuesta'=>false
                  ]);
            }

        }
        catch (Cartalyst\Sentry\Users\LoginRequiredException $e)
        {
            echo 'Login field is required.';
        }
        catch (Cartalyst\Sentry\Users\PasswordRequiredException $e)
        {
            echo 'Password field is required.';
        }
        catch (Cartalyst\Sentry\Users\UserExistsException $e)
        {
            echo 'User with this login already exists.';
        }
        catch (Cartalyst\Sentry\Groups\GroupNotFoundException $e)
        {
            echo 'Group was not found.';
        }
      
    }

});

Route::post('crearrol', function(Request $request) {

    if (!Auth::check())
    {
        return Redirect::to('/')->with('mensaje','<i class="fa fa-exclamation-triangle"></i> Para ingresar al sistema debe loguearse primero');

    }else {

        $validaciones = [
            'nombre_rol'=>'required|unique:roles'
        ];

        $mensajes = [
            'nombre_rol.required'=>'El nombre del rol es requerido',
            'nombre_rol.unique'=>'El nombre no se puede repetir',
            //'nombre_rol.sololetrasyespacio'=>'El nombre del rol solo puede llevar letras y espacios'
        ];

        $validador = Validator::make($request->all(), $validaciones, $mensajes);

        if ($validador->fails()){

            return Response::json([
                'respuesta'=>false,
                'errores'=>$validador->errors()->getMessages(),
                'all'=>$request->all()
            ]);

        }else {

            $array = ["portalusuarios" => [
                        "admin" => [
                        "ver" => $request->portalusuarios_admin_ver,
                        ],
                        "qrusers" => [
                        "ver" => $request->portalusuarios_qrusers_ver,
                        ],
                        "bancos" => [
                        "ver" => $request->portalusuarios_bancos_ver,
                        ],
                        "ejecutivo" => [
                        "ver" => $request->portalusuarios_ejecutivo_ver,
                        ],
                        "gestiondocumental" => [
                        "ver" => $request->portalusuarios_gestiondocumental_ver,
                        ],
                    ],
                    "portalproveedores" => [
                        "documentacion" => [
                        "ver" => $request->portalproveedores_documentacion_ver,
                        "creacion" => $request->portalproveedores_documentacion_creacion,
                        ],
                        "cuentasdecobro" => [
                        "ver" => $request->portalproveedores_cuentasdecobro_ver,
                        "creacion" => $request->portalproveedores_cuentasdecobro_creacion,
                        "historial" => $request->portalproveedores_cuentasdecobro_historial,
                        ],
                    ],
                    "escolar" => [
                        "gestion" => [
                        "ver" => $request->escolar_gestion_ver,
                        ],
                    ],
                    "transporteescolar" => [
                        "gestionusuarios" => [
                        "ver" => $request->transporteescolar_gestionusuarios_ver,
                        "creacion" => $request->transporteescolar_gestionusuarios_creacion,
                        ],
                    ],
                    "transportes" => [
                        "plan_rodamiento" => [
                        "ver" => $request->transportes_plan_rodamiento_ver,
                        ],
                    ],
                    "barranquilla" => [
                    "transportesbq" => [
                        "ver" => $request->barranquilla_transportesbq_ver,
                    ],
                    "serviciosbq"=>[
                        "ver"=>$request->barranquilla_serviciosbq_ver,
                        "creacion"=> $request->barranquilla_serviciosbq_creacion,
                        "edicion"=> $request->barranquilla_serviciosbq_edicion,
                        "eliminacion"=> $request->barranquilla_serviciosbq_eliminacion
                    ],
                    "reconfirmacionbq" =>[
                        "ver"=>$request->barranquilla_reconfirmacionbq_ver,
                        "reconfirmar"=> $request->barranquilla_reconfirmacionbq_reconfirmar,
                        "alerta_reconfirmacion"=> $request->barranquilla_reconfirmacionbq_alerta_reconfirmacion
                    ],
                    "novedadbq" =>[
                        "ver"=> $request->barranquilla_novedadbq_ver,
                        "crear"=> $request->barranquilla_novedadbq_crear,
                        "editar"=> $request->barranquilla_novedadbq_editar,
                        "eliminar"=> $request->barranquilla_novedadbq_eliminar
                    ],
                    "reportesbq"=> [
                        "ver"=> $request->barranquilla_reportesbq_ver,
                        "crear"=> $request->barranquilla_reportesbq_crear
                    ],
                    "encuestabq"=> [
                        "ver"=> $request->barranquilla_encuestabq_ver,
                        "crear"=> $request->barranquilla_encuestabq_crear
                    ],
                    "constanciabq"=> [
                        "crear"=> $request->barranquilla_constanciabq_crear,
                        "edicion"=> $request->barranquilla_constanciabq_edicion
                    ],
                    "poreliminarbq"=>[
                        "ver"=> $request->barranquilla_poreliminarbq_ver,
                        "rechazar"=> $request->barranquilla_poreliminarbq_rechazar,
                        "eliminar"=> $request->barranquilla_poreliminarbq_eliminar
                    ],
                    "papeleradereciclajebq"=>[
                        "ver"=> $request->barranquilla_papeleradereciclajebq_ver
                    ],
                    "poraceptarbq"=>[
                            "ver"=> $request->barranquilla_poraceptarbq_ver,
                            "rechazar"=> $request->barranquilla_poraceptarbq_rechazar,
                            "eliminar"=> $request->barranquilla_poraceptarbq_eliminar
                        ],
                        "ejecutivosbq"=>[
                        "ver"=> $request->barranquilla_ejecutivosbq_ver,
                        "crear"=> $request->barranquilla_ejecutivosbq_crear,
                        ],
                    "afiliadosexternosbq"=>[
                            "ver"=> $request->barranquilla_afiliadosexternosbq_ver
                        ]
                ],
                "bogota" => [
                    "transportes" => [
                        "ver" => $request->bogota_transportes_ver,
                    ],
                    "servicios"=>[
                        "ver"=>$request->bogota_servicios_ver,
                        "creacion"=> $request->bogota_servicios_creacion,
                        "edicion"=> $request->bogota_servicios_edicion,
                        "eliminacion"=> $request->bogota_servicios_eliminacion
                    ],
                    "reconfirmacion" =>[
                        "ver"=>$request->bogota_reconfirmacion_ver,
                        "reconfirmar"=> $request->bogota_reconfirmacion_reconfirmar,
                        "alerta_reconfirmacion"=> $request->bogota_reconfirmacion_alerta_reconfirmacion
                    ],
                    "novedad" =>[
                        "ver"=> $request->bogota_novedad_ver,
                        "crear"=> $request->bogota_novedad_crear,
                        "editar"=> $request->bogota_novedad_editar,
                        "eliminar"=> $request->bogota_novedad_eliminar
                    ],
                    "reportes"=> [
                        "ver"=> $request->bogota_reportes_ver,
                        "crear"=> $request->bogota_reportes_crear
                    ],
                    "encuesta"=> [
                        "ver"=> $request->bogota_encuesta_ver,
                        "crear"=> $request->bogota_encuesta_crear
                    ],
                    "constancia"=> [
                        "crear"=> $request->bogota_constancia_crear,
                        "edicion"=> $request->bogota_constancia_edicion
                    ],
                    "poreliminar"=>[
                        "ver"=> $request->bogota_poreliminar_ver,
                        "rechazar"=> $request->bogota_poreliminar_rechazar,
                        "eliminar"=> $request->bogota_poreliminar_eliminar
                    ],
                    "papeleradereciclaje"=>[
                        "ver"=> $request->bogota_papeleradereciclaje_ver
                    ],
                    "poraceptar"=>[
                            "ver"=> $request->bogota_poraceptar_ver,
                            "rechazar"=> $request->bogota_poraceptar_rechazar,
                            "eliminar"=> $request->bogota_poraceptar_eliminar
                        ],
                        "ejecutivos"=>[
                        "ver"=> $request->bogota_ejecutivos_ver,
                        "crear"=> $request->bogota_ejecutivos_crear,
                    ],
                    "afiliadosexternos"=>[
                            "ver"=> $request->bogota_afiliadosexternos_ver
                        ]
                ],
                "otrostransporte" => [
                    "otrostransporte" => [
                        "ver" => $request->otrostransporte_otrostransporte_ver,
                    ]
                ],

                //
                "transportes" => [
                    "plan_rodamiento" => [
                        "ver" => $request->transportes_plan_rodamiento_ver,
                    ]
                ],
                "turismo"=>[
                    "otros"=>[
                        "ver"=> $request->turismo_otros_ver,
                        "crear"=> $request->turismo_otros_crear
                    ]
                ],
                "comercial"=>[
                    "cotizaciones"=>[
                        "ver"=>$request->comercial_cotizaciones_crear,
                        "crear"=>$request->comercial_cotizaciones_ver
                    ]
                ],
                "facturacion"=>[
                    "revision"=> [
                        "ver"=> $request->facturacion_revision_ver,
                        "crear"=> $request->facturacion_revision_crear
                    ],
                    "liquidacion"=> [
                        "ver"=> $request->facturacion_liquidacion_ver,
                        "liquidar"=> $request->facturacion_liquidacion_liquidar,
                        "generar_liquidacion"=> $request->facturacion_liquidacion_generar_liquidacion
                    ],
                    "autorizar"=> [
                        "ver"=> $request->facturacion_autorizar_ver,
                        "autorizar"=> $request->facturacion_autorizar_autorizar,
                        "anular"=> $request->facturacion_autorizar_anular,
                        "generar_factura"=> $request->facturacion_autorizar_generar_factura
                    ],
                    "ordenes_de_facturacion"=> [
                        "ver"=> $request->facturacion_ordenes_de_facturacion_ver,
                        "anular"=> $request->facturacion_ordenes_de_facturacion_anular,
                        "ingreso"=> $request->facturacion_ordenes_de_facturacion_ingreso,
                        "ingreso_imagenes"=> $request->facturacion_ordenes_de_facturacion_ingreso_imagenes,
                        "revision"=> $request->facturacion_ordenes_de_facturacion_revision
                    ]
                ],
                "contabilidad"=>[
                    "pago_proveedores"=>[
                        "ver"=> $request->contabilidad_pago_proveedores_ver,
                        "generar_orden_pago" => $request->contabilidad_pago_proveedores_generar_orden_pago
                    ],
                    "factura_proveedores"=>[
                        "ver"=>$request->contabilidad_factura_proveedores_ver,
                        "cerrar_pago"=> $request->contabilidad_factura_proveedores_cerrar_pago,
                        "revisar"=>$request->contabilidad_factura_proveedores_revisar,
                        "anular"=>$request->contabilidad_factura_proveedores_anular
                    ],
                    "listado_de_pagos_preparar"=>[
                        "ver"=> $request->contabilidad_listado_de_pagos_preparar_ver,
                        "preparar"=>$request->contabilidad_listado_de_pagos_preparar_preparar
                    ],
                    "listado_de_pagos_auditar"=>[
                        "ver"=> $request->contabilidad_listado_de_pagos_auditar_ver,
                        "auditar"=>$request->contabilidad_listado_de_pagos_auditar_auditar
                    ],
                    "listado_de_pagos_autorizar"=>[
                        "ver"=>$request->contabilidad_listado_de_pagos_autorizar_ver,
                        "autorizar"=>$request->contabilidad_listado_de_pagos_autorizar_autorizar
                    ],
                    "listado_de_pagados"=>[
                        "ver"=>$request->contabilidad_listado_de_pagados_ver,
                    ],
                    "comisiones"=>[
                        "ver"=>$request->contabilidad_comisiones_ver,
                        "generar_pago"=>$request->contabilidad_comisiones_generar_pago
                    ],
                    "pago_de_comisiones"=>[
                        "ver"=>$request->contabilidad_pago_de_comisiones_ver,
                        "revisar"=>$request->contabilidad_pago_de_comisiones_revisar
                    ],
                    "pagos_por_autorizar_comision"=>[
                        "ver"=> $request->contabilidad_pagos_por_autorizar_comision_ver,
                        "autorizar"=> $request->contabilidad_pagos_por_autorizar_comision_autorizar,
                    ],
                    "pagos_por_pagar_comision"=>[
                        "ver"=>$request->contabilidad_pagos_por_pagar_comision_ver
                    ],
                ],
                "turismo"=>[
                    "otros"=>[
                        "ver"=>$request->turismo_otros_ver,
                        "crear"=>$request->turismo_otros_crear
                    ]
                ],
                "comercial"=>[
                    "cotizaciones"=>[
                        "ver"=>$request->comercial_cotizaciones_ver,
                        "crear"=>$request->comercial_cotizaciones_crear,
                        "editar"=>$request->comercial_cotizaciones_editar
                    ]
                ],
                "administrativo"=>[
                    "centros_de_costo"=>[
                        "ver" => $request->administrativo_centros_de_costo_ver,
                        "crear" => $request->administrativo_centros_de_costo_crear,
                        "editar" => $request->administrativo_centros_de_costo_editar,
                        "bloquear_desbloquear" => $request->administrativo_centros_de_costo_bloquear_desbloquear,
                    ],
                    "proveedores"=>[
                        "ver" => $request->administrativo_proveedores_ver,
                        "crear" => $request->administrativo_proveedores_crear,
                        "editar" => $request->administrativo_proveedores_editar,
                        "bloquear_desbloquear" => $request->administrativo_proveedores_bloquear_desbloquear,
                        "listado_vehiculos" => $request->administrativo_proveedores_listado_vehiculos,
                        "listado_conductores" => $request->administrativo_proveedores_listado_conductores,
                        "bloqueo_conductores" => $request->administrativo_proveedores_bloqueo_conductores,
                        "bloqueo_vehiculos" => $request->administrativo_proveedores_bloqueo_vehiculos,
                    ],
                    "administracion_proveedores"=>[
                        "ver"=> $request->administrativo_administracion_proveedores_ver,
                        "crear"=> $request->administrativo_administracion_proveedores_crear
                    ],
                    "contratos"=>[
                        "ver" => $request->administrativo_contratos_ver,
                        "crear" => $request->administrativo_contratos_crear,
                        "editar" => $request->administrativo_contratos_editar,
                        "renovar" => $request->administrativo_contratos_renovar
                    ],
                    "seguridad_social"=>[
                        "ver"=>$request->administrativo_seguridad_social_ver,
                        "crear"=>$request->administrativo_seguridad_social_crear
                    ],
                    "fuec"=>[
                        "ver"=> $request->administrativo_fuec_ver,
                        "crear"=> $request->administrativo_fuec_crear,
                        "editar"=> $request->administrativo_fuec_editar,
                        "descargar"=> $request->administrativo_fuec_descargar,
                        "rutas_fuec"=>$request->administrativo_fuec_rutas_fuec
                    ],
                    "rutas_y_tarifas"=>[
                        "ver"=> $request->administrativo_rutas_y_tarifas_ver,
                        "editar"=>$request->administrativo_rutas_y_tarifas_editar
                    ],
                    "ciudades"=>[
                        "ver"=>$request->administrativo_ciudades_ver,
                        "crear"=>$request->administrativo_ciudades_crear,
                        "editar"=>$request->administrativo_ciudades_editar
                    ]
                ],
                "talentohumano"=>[
                    "empleados"=> [
                    "ver"=> $request->talentohumano_empleados_ver,
                    "crear"=> $request->talentohumano_empleados_crear,
                    "editar"=> $request->talentohumano_empleados_editar,
                    "retirar"=> $request->talentohumano_empleados_retirar
                    ],
                    "prestamos"=> [
                    "ver"=> $request->talentohumano_prestamos_ver,
                    "crear"=> $request->talentohumano_prestamos_crear,
                    "gestionar"=> $request->talentohumano_prestamos_gestionar
                    ],
                    "vacaciones"=> [
                    "ver"=> $request->talentohumano_vacaciones_ver,
                    "crear"=> $request->talentohumano_vacaciones_crear
                    ],
                    "control_ingreso"=> [
                    "ver"=> $request->talentohumano_control_ingreso_ver,
                    "crear"=> $request->talentohumano_control_ingreso_crear,
                    "guardar_personal"=> $request->talentohumano_control_ingreso_guardar_personal,
                    "historial"=> $request->talentohumano_control_ingreso_historial
                    ],
                    "control_ingreso_bog"=> [
                    "ver"=> $request->talentohumano_control_ingreso_bog_ver,
                    "crear"=> $request->talentohumano_control_ingreso_bog_crear,
                    "guardar_personal_bog"=> $request->talentohumano_control_ingreso_bog_guardar_personal_bog,
                    "historial"=> $request->talentohumano_control_ingreso_bog_historial
                    ]
                ],
                "gestion_integral"=>[
                    "indicadores"=>[
                    "ver"=> $request->gestion_integral_indicadores_ver,
                    "crear"=> $request->gestion_integral_indicadores_crear,
                    "editar"=> $request->gestion_integral_indicadores_editar,
                    "eliminar"=> $request->gestion_integral_indicadores_eliminar
                    ]
                ],
                "administracion"=>[
                    "usuarios" =>[
                        "ver"=> $request->administracion_usuarios_ver
                    ],
                    "clientes_particulares" => [
                        "ver" => $request->administracion_clientes_particulares_ver
                    ],
                    "clientes_empresariales" => [
                        "ver" => $request->administracion_clientes_empresariales_ver
                    ],
                    "importar_pasajeros" => [
                        "ver" => $request->administracion_importar_pasajeros_ver
                    ],
                    "listado_pasajeros" => [
                        "ver" => $request->administracion_listado_pasajeros_ver
                    ]
                ],
                "mobile"=>[
                    "servicios_programados_sintarifa" =>[
                        "ver"=> $request->mobile_servicios_programados_sintarifa_ver
                    ],
                    "servicios_programados_tarifado" =>[
                        "ver"=> $request->mobile_servicios_programados_tarifado_ver
                    ],
                    "servicios_programados_pagados" =>[
                        "ver"=> $request->mobile_servicios_programados_pagados_ver
                    ],
                    "servicios_programados_facturacion" =>[
                        "ver"=> $request->mobile_servicios_programados_facturacion_ver
                    ],
                    "servicios_programados" =>[
                        "ver"=> $request->mobile_servicios_programados_ver
                    ]
                ]
            ];

            $rol = new Rol();
            $rol->nombre_rol = strtoupper($request->nombre_rol);
            $rol->permisos = json_encode($array);
            $rol->creado_por = Auth::user()->id;

            if ($rol->save()){

                return Response::json([
                    'respuesta'=>true,
                    'arrayRoles'=>json_encode($array),
                    'all'=>$request->all()
                ]);

            }
        }
    }

});

Route::post('editarrol', function(Request $request) {

    if (!Auth::check()){

        return Response::json([
            'respuesta'=>'relogin'
        ]);

    }else {

        $array = ["portalusuarios" => [
                        "admin" => [
                        "ver" => $request->portalusuarios_admin_ver,
                        ],
                        "qrusers" => [
                        "ver" => $request->portalusuarios_qrusers_ver,
                        ],
                        "bancos" => [
                        "ver" => $request->portalusuarios_bancos_ver,
                        ],
                        "ejecutivo" => [
                        "ver" => $request->portalusuarios_ejecutivo_ver,
                        ],
                        "gestiondocumental" => [
                        "ver" => $request->portalusuarios_gestiondocumental_ver,
                        ],
                    ],
                    "portalproveedores" => [
                        "documentacion" => [
                        "ver" => $request->portalproveedores_documentacion_ver,
                        "creacion" => $request->portalproveedores_documentacion_creacion,
                        ],
                        "cuentasdecobro" => [
                        "ver" => $request->portalproveedores_cuentasdecobro_ver,
                        "creacion" => $request->portalproveedores_cuentasdecobro_creacion,
                        "historial" => $request->portalproveedores_cuentasdecobro_historial,
                        ]
                    ],
                    "escolar" => [
                        "gestion" => [
                        "ver" => $request->escolar_gestion_ver,
                        ],
                    ],
                    "transporteescolar" => [
                        "gestionusuarios" => [
                        "ver" => $request->transporteescolar_gestionusuarios_ver,
                        "creacion" => $request->transporteescolar_gestionusuarios_creacion,
                        ],
                    ],
                    "barranquilla" => [
                            "transportesbq" => [
                                "ver" => $request->barranquilla_transportesbq_ver,
                    ],
                    "serviciosbq"=>[
                        "ver"=>$request->barranquilla_serviciosbq_ver,
                        "creacion"=> $request->barranquilla_serviciosbq_creacion,
                        "edicion"=> $request->barranquilla_serviciosbq_edicion,
                        "eliminacion"=> $request->barranquilla_serviciosbq_eliminacion
                    ],
                    "reconfirmacionbq" =>[
                        "ver"=>$request->barranquilla_reconfirmacionbq_ver,
                        "reconfirmar"=> $request->barranquilla_reconfirmacionbq_reconfirmar,
                        "alerta_reconfirmacion"=> $request->barranquilla_reconfirmacionbq_alerta_reconfirmacion
                    ],
                    "novedadbq" =>[
                        "ver"=> $request->barranquilla_novedadbq_ver,
                        "crear"=> $request->barranquilla_novedadbq_crear,
                        "editar"=> $request->barranquilla_novedadbq_editar,
                        "eliminar"=> $request->barranquilla_novedadbq_eliminar
                    ],
                    "reportesbq"=> [
                        "ver"=> $request->barranquilla_reportesbq_ver,
                        "crear"=> $request->barranquilla_reportesbq_crear
                    ],
                    "encuestabq"=> [
                        "ver"=> $request->barranquilla_encuestabq_ver,
                        "crear"=> $request->barranquilla_encuestabq_crear
                    ],
                    "constanciabq"=> [
                        "crear"=> $request->barranquilla_constanciabq_crear,
                        "edicion"=> $request->barranquilla_constanciabq_edicion
                    ],
                    "poreliminarbq"=>[
                        "ver"=> $request->barranquilla_poreliminarbq_ver,
                        "rechazar"=> $request->barranquilla_poreliminarbq_rechazar,
                        "eliminar"=> $request->barranquilla_poreliminarbq_eliminar
                    ],
                    "papeleradereciclajebq"=>[
                        "ver"=> $request->barranquilla_papeleradereciclajebq_ver
                    ],
                    "poraceptarbq"=>[
                            "ver"=> $request->barranquilla_poraceptarbq_ver,
                            "rechazar"=> $request->barranquilla_poraceptarbq_rechazar,
                            "eliminar"=> $request->barranquilla_poraceptarbq_eliminar
                        ],
                        "ejecutivosbq"=>[
                        "ver"=> $request->barranquilla_ejecutivosbq_ver,
                        "crear"=> $request->barranquilla_ejecutivosbq_crear,
                        ],
                    "afiliadosexternosbq"=>[
                            "ver"=> $request->barranquilla_afiliadosexternosbq_ver
                        ]
                ],
                "bogota" => [
                    "transportes" => [
                        "ver" => $request->bogota_transportes_ver,
                    ],
                    "servicios"=>[
                        "ver"=>$request->bogota_servicios_ver,
                        "creacion"=> $request->bogota_servicios_creacion,
                        "edicion"=> $request->bogota_servicios_edicion,
                        "eliminacion"=> $request->bogota_servicios_eliminacion
                    ],
                    "reconfirmacion" =>[
                        "ver"=>$request->bogota_reconfirmacion_ver,
                        "reconfirmar"=> $request->bogota_reconfirmacion_reconfirmar,
                        "alerta_reconfirmacion"=> $request->bogota_reconfirmacion_alerta_reconfirmacion
                    ],
                    "novedad" =>[
                        "ver"=> $request->bogota_novedad_ver,
                        "crear"=> $request->bogota_novedad_crear,
                        "editar"=> $request->bogota_novedad_editar,
                        "eliminar"=> $request->bogota_novedad_eliminar
                    ],
                    "reportes"=> [
                        "ver"=> $request->bogota_reportes_ver,
                        "crear"=> $request->bogota_reportes_crear
                    ],
                    "encuesta"=> [
                        "ver"=> $request->bogota_encuesta_ver,
                        "crear"=> $request->bogota_encuesta_crear
                    ],
                    "constancia"=> [
                        "crear"=> $request->bogota_constancia_crear,
                        "edicion"=> $request->bogota_constancia_edicion
                    ],
                    "poreliminar"=>[
                        "ver"=> $request->bogota_poreliminar_ver,
                        "rechazar"=> $request->bogota_poreliminar_rechazar,
                        "eliminar"=> $request->bogota_poreliminar_eliminar
                    ],
                    "papeleradereciclaje"=>[
                        "ver"=> $request->bogota_papeleradereciclaje_ver
                    ],
                    "poraceptar"=>[
                            "ver"=> $request->bogota_poraceptar_ver,
                            "rechazar"=> $request->bogota_poraceptar_rechazar,
                            "eliminar"=> $request->bogota_poraceptar_eliminar
                        ],
                        "ejecutivos"=>[
                        "ver"=> $request->bogota_ejecutivos_ver,
                        "crear"=> $request->bogota_ejecutivos_crear,
                    ],
                    "afiliadosexternos"=>[
                            "ver"=> $request->bogota_afiliadosexternos_ver
                        ]
                ],
                "otrostransporte" => [
                    "otrostransporte" => [
                        "ver" => $request->otrostransporte_otrostransporte_ver,
                    ]
                ],

            "transportes" =>[
                "plan_rodamiento" => [
                    "ver" => $request->transportes_plan_rodamiento_ver
                ],
            ],
            "turismo"=>[
                "otros"=>[
                    "ver"=> $request->turismo_otros_ver,
                    "crear"=> $request->turismo_otros_crear
                ]
            ],
            "facturacion"=>[
                "revision"=> [
                    "ver"=> $request->facturacion_revision_ver,
                    "crear"=> $request->facturacion_revision_crear
                ],
                "liquidacion"=> [
                    "ver"=> $request->facturacion_liquidacion_ver,
                    "liquidar"=> $request->facturacion_liquidacion_liquidar,
                    "generar_liquidacion"=> $request->facturacion_liquidacion_generar_liquidacion
                ],
                "autorizar"=> [
                    "ver"=> $request->facturacion_autorizar_ver,
                    "autorizar"=> $request->facturacion_autorizar_autorizar,
                    "anular"=> $request->facturacion_autorizar_anular,
                    "generar_factura"=> $request->facturacion_autorizar_generar_factura
                ],
                "ordenes_de_facturacion"=> [
                    "ver"=> $request->facturacion_ordenes_de_facturacion_ver,
                    "anular"=> $request->facturacion_ordenes_de_facturacion_anular,
                    "ingreso"=> $request->facturacion_ordenes_de_facturacion_ingreso,
                    "ingreso_imagenes"=> $request->facturacion_ordenes_de_facturacion_ingreso_imagenes,
                    "revision"=> $request->facturacion_ordenes_de_facturacion_revision
                ]
            ],
            "contabilidad"=>[
                "pago_proveedores"=>[
                    "ver"=> $request->contabilidad_pago_proveedores_ver,
                    "generar_orden_pago" => $request->contabilidad_pago_proveedores_generar_orden_pago
                ],
                "factura_proveedores"=>[
                    "ver"=>$request->contabilidad_factura_proveedores_ver,
                    "cerrar_pago"=> $request->contabilidad_factura_proveedores_cerrar_pago,
                    "revisar"=>$request->contabilidad_factura_proveedores_revisar,
                    "anular"=>$request->contabilidad_factura_proveedores_anular
                ],
                "listado_de_pagos_preparar"=>[
                    "ver"=> $request->contabilidad_listado_de_pagos_preparar_ver,
                    "preparar"=>$request->contabilidad_listado_de_pagos_preparar_preparar
                ],
                "listado_de_pagos_auditar"=>[
                    "ver"=> $request->contabilidad_listado_de_pagos_auditar_ver,
                    "auditar"=>$request->contabilidad_listado_de_pagos_auditar_auditar
                ],
                "listado_de_pagos_autorizar"=>[
                    "ver"=>$request->contabilidad_listado_de_pagos_autorizar_ver,
                    "autorizar"=>$request->contabilidad_listado_de_pagos_autorizar_autorizar
                ],
                "listado_de_pagados"=>[
                    "ver"=>$request->contabilidad_listado_de_pagados_ver,
                ],
                "comisiones"=>[
                    "ver"=>$request->contabilidad_comisiones_ver,
                    "generar_pago"=>$request->contabilidad_comisiones_generar_pago
                ],
                "pago_de_comisiones"=>[
                    "ver"=>$request->contabilidad_pago_de_comisiones_ver,
                    "revisar"=>$request->contabilidad_pago_de_comisiones_revisar
                ],
                "pagos_por_autorizar_comision"=>[
                    "ver"=> $request->contabilidad_pagos_por_autorizar_comision_ver,
                    "autorizar"=> $request->contabilidad_pagos_por_autorizar_comision_autorizar,
                ],
                "pagos_por_pagar_comision"=>[
                    "ver"=>$request->contabilidad_pagos_por_pagar_comision_ver
                ],
            ],
            "comercial"=>[
                "cotizaciones"=>[
                    "ver"=>$request->comercial_cotizaciones_ver,
                    "crear"=>$request->comercial_cotizaciones_crear,
                    "editar"=>$request->comercial_cotizaciones_editar
                ]
            ],
            "administrativo"=>[
                "centros_de_costo"=>[
                    "ver"=>$request->administrativo_centros_de_costo_ver,
                    "crear"=>$request->administrativo_centros_de_costo_crear,
                    "editar"=>$request->administrativo_centros_de_costo_editar,
                    "bloquear_desbloquear"=>$request->administrativo_centros_de_costo_bloquear_desbloquear,
                ],
                "proveedores"=>[
                    "ver"=>$request->administrativo_proveedores_ver,
                    "crear"=>$request->administrativo_proveedores_crear,
                    "editar"=>$request->administrativo_proveedores_editar,
                    "bloquear_desbloquear"=>$request->administrativo_proveedores_bloquear_desbloquear,
                    "listado_vehiculos"=>$request->administrativo_proveedores_listado_vehiculos,
                    "listado_conductores"=>$request->administrativo_proveedores_listado_conductores,
                    "bloqueo_conductores" => $request->administrativo_proveedores_bloqueo_conductores,
                    "bloqueo_vehiculos" => $request->administrativo_proveedores_bloqueo_vehiculos,
                ],
                "administracion_proveedores"=>[
                    "ver"=> $request->administrativo_administracion_proveedores_ver,
                    "crear"=> $request->administrativo_administracion_proveedores_crear
                ],
                "contratos"=>[
                    "ver" => $request->administrativo_contratos_ver,
                    "crear" => $request->administrativo_contratos_crear,
                    "editar" => $request->administrativo_contratos_editar,
                    "renovar" => $request->administrativo_contratos_renovar
                ],
                "seguridad_social"=>[
                    "ver"=>$request->administrativo_seguridad_social_ver,
                    "crear"=>$request->administrativo_seguridad_social_crear
                ],
                "fuec"=>[
                    "ver"=> $request->administrativo_fuec_ver,
                    "crear"=> $request->administrativo_fuec_crear,
                    "editar"=> $request->administrativo_fuec_editar,
                    "descargar"=> $request->administrativo_fuec_descargar,
                    "rutas_fuec"=>$request->administrativo_fuec_rutas_fuec
                ],
                "rutas_y_tarifas"=>[
                    "ver"=> $request->administrativo_rutas_y_tarifas_ver,
                    "editar"=>$request->administrativo_rutas_y_tarifas_editar
                ],
                "ciudades"=>[
                    "ver"=>$request->administrativo_ciudades_ver,
                    "crear"=>$request->administrativo_ciudades_crear,
                    "editar"=>$request->administrativo_ciudades_editar
                ]
            ],
            "talentohumano"=>[
                    "empleados"=> [
                    "ver"=> $request->talentohumano_empleados_ver,
                    "crear"=> $request->talentohumano_empleados_crear,
                    "editar"=> $request->talentohumano_empleados_editar,
                    "retirar"=> $request->talentohumano_empleados_retirar
                    ],
                    "prestamos"=> [
                    "ver"=> $request->talentohumano_prestamos_ver,
                    "crear"=> $request->talentohumano_prestamos_crear,
                    "gestionar"=> $request->talentohumano_prestamos_gestionar
                    ],
                    "vacaciones"=> [
                    "ver"=> $request->talentohumano_vacaciones_ver,
                    "crear"=> $request->talentohumano_vacaciones_crear
                    ],
                    "control_ingreso"=> [
                    "ver"=> $request->talentohumano_control_ingreso_ver,
                    "crear"=> $request->talentohumano_control_ingreso_crear,
                    "guardar_personal"=> $request->talentohumano_control_ingreso_guardar_personal,
                    "historial"=> $request->talentohumano_control_ingreso_historial
                    ],
                    "control_ingreso_bog"=> [
                    "ver"=> $request->talentohumano_control_ingreso_bog_ver,
                    "crear"=> $request->talentohumano_control_ingreso_bog_crear,
                    "guardar_personal_bog"=> $request->talentohumano_control_ingreso_bog_guardar_personal_bog,
                    "historial"=> $request->talentohumano_control_ingreso_bog_historial
                    ]
                ],
                "gestion_integral"=>[
                    "indicadores"=>[
                    "ver"=> $request->gestion_integral_indicadores_ver,
                    "crear"=> $request->gestion_integral_indicadores_crear,
                    "editar"=> $request->gestion_integral_indicadores_editar,
                    "eliminar"=> $request->gestion_integral_indicadores_eliminar
                    ]
                ],
            "administracion"=>[
                "usuarios" =>[
                    "ver"=> $request->administracion_usuarios_ver
                ],
                "clientes_particulares" => [
                    "ver" => $request->administracion_clientes_particulares_ver
                ],
                "clientes_empresariales" => [
                    "ver" => $request->administracion_clientes_empresariales_ver
                ],
                "importar_pasajeros" => [
                    "ver" => $request->administracion_importar_pasajeros_ver
                ],
                "listado_pasajeros" => [
                    "ver" => $request->administracion_listado_pasajeros_ver
                ]
            ],
            "mobile"=>[
                "servicios_programados_sintarifa" =>[
                    "ver"=> $request->mobile_servicios_programados_sintarifa_ver
                ],
                "servicios_programados_tarifado" =>[
                    "ver"=> $request->mobile_servicios_programados_tarifado_ver
                ],
                "servicios_programados_pagados" =>[
                    "ver"=> $request->mobile_servicios_programados_pagados_ver
                ],
                "servicios_programados_facturacion" =>[
                    "ver"=> $request->mobile_servicios_programados_facturacion_ver
                ],
                "servicios_programados" =>[
                    "ver"=> $request->mobile_servicios_programados_ver
                ]
            ]
        ];

        $rol = Rol::find($request->id);
        $rol->nombre_rol = strtoupper($request->nombre_rol);
        $rol->permisos = json_encode($array);

        if ($rol->save()){

            return Response::json([
                'respuesta'=>true
            ]);
        }
    }    

});

Route::post('verrolusuario', function(Request $request) {

    if (!Auth::check()){

        return Response::json([
            'respuesta'=>'relogin'
        ]);

    }else {


        $user = DB::table('users')
        ->select('users.id','roles.id as id_rol','roles.nombre_rol')
        ->leftJoin('roles','users.id_rol','=','roles.id')
        ->where('users.id',$request->id)
        ->first();

        if ($user){
            return Response::json([
                'respuesta'=>true,
                'user'=>$user
            ]);
        }else {
            return Response::json([
                'respuesta'=>false,
                'user'=>$user
            ]);
        }

    }

});

Route::post('cambiarrolusuario', function(Request $request) {

    if (!Auth::check()){

        return Response::json([
            'respuesta'=>'relogin'
        ]);

    }else {

        $user = User::find($request->id);
        $user->id_rol = $request->rol;

        if ($user->save()){
            return Response::json([
                'respuesta'=>true
            ],200);
        }

    }
});

Route::post('cambiarcontrasena', function(Request $request) {

    if (!Auth::check()){

        return Response::json([
            'respuesta'=>'relogin'
        ]);

    }else{

        try {

            $contrasena = $request->contrasena;
            $id = $request->id;
            $user = User::find($id);

            $user->password = Hash::make($contrasena);

            if ($user->save()){

                return Response::json([
                'respuesta'=>true
                ]);
            }

        } catch (Exception $e) {
            return Response::json([
                'e'=>$e
            ]);
        }

    }
  
});

/*Centros de costo START*/
Route::get('centrodecosto', function(Request $request) {

    if (Auth::check()) {
        $id_rol = Auth::user()->id_rol;
        $permisos = DB::table('roles')->where('id',$id_rol)->first();
        $permisos = json_decode($permisos->permisos);
    }else{
        $id_rol = null;
        $permisos = null;
        $permisos = null;
    }

    if (isset($permisos->administrativo->centros_de_costo->ver)){
        $ver = $permisos->administrativo->centros_de_costo->ver;
    }else{
        $ver = null;
    }

    if (!Auth::check()){

        return Redirect::to('/')->with('mensaje','<i class="fa fa-exclamation-triangle"></i> Para ingresar al sistema debe loguearse primero');

    }else if($ver!='on' ) {
        return View::make('admin.permisos');
    }else {

        $centrosdecosto = DB::table('centrosdecosto')
        ->select('asesor_comercial.nombre_completo','asesor_comercial.id','centrosdecosto.id','centrosdecosto.nit','centrosdecosto.codigoverificacion','centrosdecosto.razonsocial',
                            'centrosdecosto.tipoempresa','centrosdecosto.direccion','centrosdecosto.ciudad','centrosdecosto.departamento','centrosdecosto.email','centrosdecosto.telefono',
                            'centrosdecosto.asesor_comercial','centrosdecosto.inactivo','centrosdecosto.inactivo_total')
        ->leftJoin('asesor_comercial','centrosdecosto.asesor_comercial','=','asesor_comercial.id')
        ->orderBy('razonsocial')->get();

        $asesor_comercial = DB::table('asesor_comercial')->orderBy('nombre_completo')->get();
        $departamentos = DB::table('departamento')->get();

        return View::make('clientes.centrodecosto')
            ->with([
                'departamentos'=>$departamentos,
                'centrosdecosto'=>$centrosdecosto,
                'asesor_comercial'=>$asesor_comercial,
                'i'=>1,
                'permisos'=>$permisos
            ]);
    }
});
/*Centros de costo END*/

/*Proveedores START*/
Route::get('proveedores', function(Request $request) {

    if (Auth::check()) {
        $id_rol = Auth::user()->id_rol;
        $permisos = DB::table('roles')->where('id',$id_rol)->first();
        $permisos = json_decode($permisos->permisos);
    }else{
        $id_rol = null;
        $permisos = null;
        $permisos = null;
    }

    if (isset($permisos->administrativo->proveedores->ver)){
        $ver = $permisos->administrativo->proveedores->ver;
    }else{
        $ver = null;
    }

    if (!Auth::check()){

        return Redirect::to('/')->with('mensaje','<i class="fa fa-exclamation-triangle"></i> Para ingresar al sistema debe loguearse primero');

    }else if($ver!='on' ) {
        return View::make('admin.permisos');
    }else {

        $proveedores = DB::table('proveedores')
        ->whereNull('inactivo')
        ->whereNull('inactivo_total')
        ->orderBy('razonsocial')
        ->get();

        $departamentos = DB::table('departamento')->get();

        return View::make('proveedores.proveedores')
            ->with([
                'departamentos' => $departamentos,
                'proveedores' => $proveedores,
                'permisos' => $permisos
            ]);
    }

});

Route::get('proveedores/listadoconductores', function() {

    if(Auth::check()){
        $id_rol = Auth::user()->id_rol;
        $permisos = DB::table('roles')->where('id',$id_rol)->first();
        $permisos = json_decode($permisos->permisos);
    }else{
        $id_rol = null;
        $permisos = null;
        $permisos = null;
    }

    if (isset($permisos->administrativo->proveedores->ver)){
        $ver = $permisos->administrativo->proveedores->ver;
    }else{
        $ver = null;
    }

    if (!Auth::check()){

        return Redirect::to('/')->with('mensaje','<i class="fa fa-exclamation-triangle"></i> Para ingresar al sistema debe loguearse primero');

    }else if($ver!='on' ) {
        return View::make('admin.permisos');
    }else {

        /*$conductores = Conductor::whereHas('proveedor', function($query){

        $query->whereNull('eventual')->whereNull('inactivo_total');

        })->noarchivado()->whereNull('bloqueado_total')->whereNull('bloqueado')->orderBy('nombre_completo')->get();*/

        $conductores = DB::table('conductores')
        ->whereNull('id')
        ->get();

        return View::make('proveedores.conductores.listado_conductores',[
            'conductores'=>$conductores,
            'permisos'=>$permisos
        ]);
    }

});

Route::get('', function(Request $request) {

});

Route::get('', function(Request $request) {

});

Route::get('', function(Request $request) {

});

/*Proveedores END*/