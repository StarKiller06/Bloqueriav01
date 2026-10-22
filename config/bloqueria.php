<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Registro abierto
    |--------------------------------------------------------------------------
    |
    | Si está cerrado, nadie puede crear una cuenta ni una bloquería desde el
    | panel: solo entran los usuarios iniciales. Se abrirá cuando el sistema
    | se ofrezca a otras bloquerías (SaaS comercial).
    |
    */

    'registro_abierto' => (bool) env('BLOQUERIA_REGISTRO_ABIERTO', false),

    /*
    |--------------------------------------------------------------------------
    | Bloquería y usuarios iniciales
    |--------------------------------------------------------------------------
    |
    | Los crea UsuariosInicialesSeeder. Los correos viven aquí; las contraseñas
    | solo en el .env del servidor, para que nunca queden guardadas en git.
    |
    */

    'empresa' => [
        'nombre' => env('BLOQUERIA_EMPRESA_NOMBRE'),
        'nit' => env('BLOQUERIA_EMPRESA_NIT'),
    ],

    'usuarios_iniciales' => [
        [
            'nombre' => 'Manuel Velásquez',
            'email' => env('BLOQUERIA_EMAIL_ADMINISTRADOR'),
            'password' => env('BLOQUERIA_PASSWORD_ADMINISTRADOR'),
        ],
        [
            'nombre' => 'Luis Díaz',
            'email' => env('BLOQUERIA_EMAIL_PROGRAMADOR'),
            'password' => env('BLOQUERIA_PASSWORD_PROGRAMADOR'),
        ],
    ],

];
