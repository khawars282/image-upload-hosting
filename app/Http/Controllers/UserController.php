<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;
use Firebase\JWT\JWT;
use Firebase\JWT\key;
use App\Models\User;
use App\Models\Token;
use App\Models\PasswordReset;
use App\Jobs\RegisterUserMail;
use App\Http\Requests\UsersFormRequest;
use App\Http\Requests\UsersloginRequest;
use App\Http\Requests\UsersGetRequest;
use App\Http\Requests\UsersProfile;
use App\Http\Requests\UsersSendResetLinkRequest;
use App\Http\Requests\UsersSendResetRequest;
use App\Providers\servce;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    // create new user for register function
    public function userDataSaveInDB(Request $request){
        // create new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' =>Hash::make($request->password),
            'age' =>$request->age,
            'image' => $request->file('image')->store('profile_picture'),
        ]);
        return $user;
    }
    //save user in Db function
    public function register(UsersFormRequest $request)
    {
        //find user in db form table user
         $userExist= User::where('email', $request->email)->first();
         try{
             //check user found
            if (isset($userExist)) {
                //return email exist
                return response()->json(['error' => $request->messages()], 402);
            }
            // create new user data function userDataSaveInDB
            $user = $this->userDataSaveInDB($request);
            //data save in DB
            $user->save();
            //genrate url on email
            $url =url('api/EmailConfirmation/'.$request['email']);
            //send email for user registration
            RegisterUserMail::dispatch($request->email,$url);
            //response json
            return response()->json([
                'success' => true,
                'message' => 'User created',
                'data' => $user
            ], Response::HTTP_OK);
            }
        catch(Throwable $ex)
        {
            //error message
            return array('Massage'=>$ex->getMessage());
        }
    }
    // confirmEmail 
    public function confirmEmail($email){
        try{
            //find user by email
            $user= User::where('email', $email)->first();
            //Update email_verified_at time
            $user->email_verified_at =$user->email_verified_at =time();
            //save data
            $user->save();
            return $user;
        }  catch(Throwable $ex)
        {
            //error message
            return array('Massage'=>$ex->getMessage());
        }
    }
    //login function
    public function authenticate(UsersloginRequest $request)
    {
        //check user request validation
        $validator =$request->validated();
        //get two parameter
        $validator = $request->safe()->only('email', 'password');
        try{
            //check validation two parameter
            if (!isset($validator)) {
                //response json
                return response()->json(['error' => $validator->messages()], 403);
            }else{
                //find user by email
                $user= User::where('email', $request->email)->first();
                //Check user not
                if(!isset($user)){return response()->json(['messages' => 'User not found'], 400);}
                //find time verified_at user
                $email_verified_at= User::where('email_verified_at', $user->email_verified_at)->first();
                //Check email verified
                if(!isset($email_verified_at)){return response()->json(['messages' => 'Check Email'], 400);}
                //find userId from table Token
                $tokeexit = Token::where('user_id',$user->id)->first();
                //check Password
                if (!Hash::check($request->password, $user->password)) {
                    //response
                    return response()->json(['messages' => 'Worng Password'], 400);
                }
                    //check token not exist
                    if(!isset($tokeexit))
                    {
                        //create to token
                        $token = (new servce)->createToken($user->id);
                        //save token in DB
                        $tokenData = Token::create([
                            'token' => $token,
                            'user_id' => $user->id
                        ]);
                        //response user and token
                        $response = [
                            'user' => $user,
                            'token' => $token,
                        ];
                    }else{
                        ////response user and token Exist
                        $response = [
                            'user' => $user,
                            'token' => "already login : ".$tokeexit->token,
                        ];
                    }
                //response
                    return response()->json($response, 201);
                }
            }
        catch(Throwable $ex)
        {
            //error message
            return array('Massage'=>$ex->getMessage());
        }
    }
    //Token decode data
    public function getToken(Request $request){
        //Get token 
        $jwt = $request->bearerToken();
        //Check token
        if (!isset($jwt)) {
            return response([
                'message' => 'token not found'
            ]);
        }
        //Decode token
        $decoded =(new servce)->decodeToken($jwt);
        //token data
        return $decoded->data;
    }

    function logout(Request $request)
    {
        //get data from function getToken
        $userId = $this->getToken($request);
        //find userId from table token 
        $userExist = Token::where("user_id",$userId)->first();
        try{
            //check user exist
            if(isset($userExist)){
                //Delete exist user
                $userExist->delete();
            
            }else{
                //response else 
                return response()->json([
                "message" => " already logged out"
                ], 404);
                
            }
            //response success
            return response()->json([ "message" => "logout success" ], 200);
        }
        catch(Throwable $ex)
        {
            //error message
            return array('Massage'=>$ex->getMessage());
        }
    }
    public function get_user(UsersGetRequest $request)
    {
        
        //check user request validation
        $validator =$request->validated();
        //get one parameter
        $validator = $request->safe()->only('token');
        try{
            //check validation Not
            if (!isset($validator)) {
                //response error
                return response()->json(['error' => $validator->messages()], 400);
            }
            //find token from table token 
            $tokenExist= Token::where('token', $request->token)->first();
            if (!isset($tokenExist)) {
                //response error
                return response()->json(['message' => 'Token Not exist'], 400);
            }
            //Decoded token
            $decoded =(new servce)->decodeToken($request->token);
            //Get data
            $userId = $decoded->data;
            // find user from table user
            $user= User::where('id', $userId)->first();
            // response
            return response()->json(['user' => $user]);
        }
        catch(Throwable $ex)
        {
            //error
            return array('Massage'=>$ex->getMessage());
        }
    }
    //Update profile every filed
    public function profile(UsersProfile $request)
    {
        try{
            //Get data
            $userId = $this->getToken($request);
            //find user from table user
            $userExist = User::where("id",$userId)->first();
            //Check user
            if(isset($userExist)){
                //Check request for name 
                if($request->name){$userExist->name = $request->name;}
                //Check request for email 
                if($request->email){$userExist->email = $request->email;}
                //Check request for age 
                if($request->age){$userExist->age = $request->age;}
                //Check request for image 
                if($request->image){$userExist->image=$request->file('image')->store('profile_picture');}
                //Save all request 
                $userExist->save();
                //response success
                return response()->json([ "message" => $userExist ], 200);
            
            }else{
                //response else
                return response()->json([ "message" => " not update" ], 404);
                
            }
        }catch(Throwable $ex){
            // response
            return array('Massage'=>$ex->getMessage());
        }
        
    }
    //create data for function sendResetLinkResponse
    public function PassworResetSaveInDB($token,Request $request){
        //create data
        $data=PasswordReset::create([
                'token' => $token,
                'email' => $request->email,
                'valid' => 0,
            ]);
            //response data
            return $data;
    }
    //send a link for forget password    
    protected function sendResetLinkResponse(UsersSendResetLinkRequest $request)
    {
       try{
           //create token
            $token = (new servce)->createToken($request->email);
            //find user exist by email
            $userExist= User::where('email', $request->email)->first();
            //check user
            if(isset($userExist)){
                //create data
                $data = $this->PassworResetSaveInDB($token,$request);
                //save data
                $data->save();
                //create a url with two parameter 
                $url =url('api/sendResetResponse/'.$request['email'].'/'.$token);
                // send email for change password
                Mail::to($request->email)->send(new ResetPasswordMail($url,'khawars282@gmail.com'));
            
            }else{
                //response else
                return response()->json(['message' => "Email could not be sent to this email address"], 404);
            }
            //response success
            $response = ['message' => 'sent to this email address'];
            return response()->json($response, 200);
        }catch(Throwable $ex){
            //error
            return array('Massage'=>$ex->getMessage());
        }
    }
    //function check user Exist 
    public function UserExistCheck($userExist){
        //check user
        if($userExist){
            //response success
            $response=['message' => "Password reset successfully"];
            return response()->json($response,200);
        }else{
            // response else
            $response = ['message' =>"Email and Token could not "];
            return response()->json($response,400);
            }
    }
    //get a link for forget password and change password 
    protected function sendResetResponse(UsersSendResetRequest $request,$email,$token){
        try{
            //find user by email
            $userExist= User::where('email', $email)->first();
            //find token form PasswordReset table
            $tokenExist =PasswordReset::where('token', $token)->first();
            //check token not
            if(!isset($tokenExist)){
                //response not found
                $response = ['message' =>"Token not found "];
                return response()->json($response,400);
                }
                //check user not
                if(!isset($userExist)){
                    //response not found
                    $response = ['message' =>"User not found "];
                    return response()->json($response,400);
                }
            // Decoded token
            $decoded =(new servce)->decodeToken($token);
            //Get data
            $userEmail = $decoded->data;
                /*check user Exist email equle token email
                                AND
                 check token Exist valid equle 0*/
                if($userExist->email == $userEmail && $tokenExist->valid == 0){
                    //change validation    
                    $tokenExist->valid = $validTrue =1;
                    //save validation
                    $tokenExist->save();
                    //check password request
                    if($request->password){
                        // change password
                        $userExist->password = Hash::make($request->password);
                    }
                    // save password
                    $userExist->save();
                    //return check user function
                    return $this->UserExistCheck($userExist);
                }
            //response
            $response = ['Password alraedy change validtion '=>$tokenExist->valid];
            return response()->json($response,201);
        }catch(Throwable $ex){
            //error Massage
            return array('Massage'=>$ex->getMessage());
        }
        
        
    }

}

