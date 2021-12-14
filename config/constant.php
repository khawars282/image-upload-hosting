<?php

/*
 * This file is part of jwt-auth.
*/
return [

    /*
    JWT Authentication Secret
    */

    'secret' => env('JWT_SECRET'),
 
    /*
    JWT hashing algorithm
    */

    'code' => env('CODE'),

    /*
    Required data
    */

    'required_jwt_data' => [
        "iss" => "http://127.0.0.1:8000",
        "aud" => "http://127.0.0.1:8000/api",
        "iat" => time(),
        "nbf" => 1357000000,
    ],

];