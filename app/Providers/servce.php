<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class servce 
{
    // function create token 
    public function createToken($data)
    {
        // get key from .env
        $key = config('constant.secret');
        // get array data from .env 
        $payload = array(
            config('constant.required_jwt_data'),
            "data" => $data,
        );
        try
        {
            //upload data in jwt token 
             $jwt = JWT::encode($payload,$key, 'HS256');
             //token
             return $jwt;
        }
         catch(Exception $ex)
         {
             //response error
             return array('error'=>$ex->getMessage());
         }
    }
    // function create token 
    public function decodeToken($jwt)
         {
            // get key from .env
            $key = config('constant.secret'); 
            //decoded token
            $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
        return $decoded;
    }
}
