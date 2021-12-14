<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Validator;

use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\key;
use App\Models\User;
use App\Models\Token;
use App\Models\Photo;
use App\Models\PhotoPrivacy;

use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;

use App\Http\Requests\PhotoSendLinkPrivacyRequest;
use App\Http\Requests\PhotoLinkPrivacyAccessRequest;

use App\Http\Requests\UploadPhotoRequest;
use App\Providers\servce;

class PhotoController extends Controller
{
    //function decod data
    public function getToken(Request $request){
        //Get token 
        $jwt = $request->bearerToken();
        //Decode token
        $decoded =(new servce)->decodeToken($jwt);
        //token data
        return $decoded->data;
    }
    //function Upload Photo save in DB
    public function uploadPhoto(UploadPhotoRequest $request){
        //Pick token data
        $user = $this->getToken($request);
        //Find login user exist 
        $userExist = User::where("id",$user)->first();
        try{
            //Check user Exist
            if(isset($userExist)){
            //Pick id 
            $id = $userExist->id;
                //check file
                if($request->hasfile('image')) 
                    { 
                        //pick image
                        $image = $request->file('image');
                        // getting image extension
                        $extension = $image->getClientOriginalExtension(); 
                        // getting image name
                        $nameWithExtension = $image->getClientOriginalName();
                        //pick name first part
                        $name = explode('.', $nameWithExtension)[0];
                        //concatinat name with extention
                        $imageName =$name.'.'.$extension;
                        //move image Pictures folder
                        $image->move('Pictures/', $imageName);
                    }
                //create photo data
                $photo = Photo::create([
                    'name' => $request->name,
                    'image' => 'Pictures/'. $imageName,
                    'extension' => $extension,
                    'privacy'=> $request->privacy,
                    'user_id'=>$userExist->id,
                    ]);
                    //save data
                    $photo->save();
                    // response success
                return response()->json(["success" => 'Upload successfully.'], 200);
                        
            }else{
                // response else
                return response()->json(["message" => "Not Upload success "], 404);
                
            }
            //response
            return response()->json([ "message" => "Upload success" ], 200);
        }
        catch(Throwable $ex)
        {
            //error
            return array('Massage'=>$ex->getMessage());
        }
        
    }
    //function Photo remove By id
    public function removePhoto(Request $request,$phototId){
        try{
            //get data token    
            $user = $this->getToken($request);
            //find userId from User table 
            $userExist = User::where("id",$user)->first();
            //check user not
            if(!isset($userExist)){
                // response no upload
                return response()->json(["massege"=>"user no remove photo"],400);
            }
            //find photoId against userId from table Photo in DB
            $photoExist = Photo::where("id",$phototId)->where("user_id",$userExist->id)->first();
            //check photo->user_id isequle user->user_id
            if(isset($photoExist->user_id)==isset($userExist->user_id)){
                //delete photo
                $photoExist->delete();
                //response success
                return response()->json([
                    "success" => 'Photo delete successfully.'
                    ], 200);
            }else{
                // response else
                return response()->json([
                    "success" => 'wrong Photo'
                    ], 403);
            }
        }catch(Throwable $ex)
        {
            // response error
            return array('Massage'=>$ex->getMessage());
        }
    }
    //all photo List function
    public function listAllPhotos(Request $request){
        try{
            //get data from token
            $user = $this->getToken($request);
            // find user_id from table Photo and pick image 
            $Photos = Photo::where('user_id', $user)->pluck('image');
            //check Photo
            if (isset($Photos)) {
                // response success
                return response()->json([
                    "Photo" => $Photos
                ],200);
            }else{
                // response wrong
                return response()->json([
                    "Photo" => 'No Data'
                ],400);
            }

        }catch(Throwable $ex)
        {
            // response error
            return array('Massage'=>$ex->getMessage());
        }
    }
    // function photo find by Name
    public function photosFindByName(Request $request,$name){
        try{
            // check name not
            if(!isset($name)){
                // response not found
                return response()->json([
                "Photo" => 'No Name '
            ],400);}
            // get data from token
            $userId = $this->getToken($request);
            // find userID against name and pick image from Photo table
            $PhotosExist = Photo::where('user_id', $userId)->where('name', $name)->pluck('image');
            //check photo 
            if (isset($PhotosExist)) {
                //response success
                return response()->json([
                    "Photo" => $PhotosExist
                ],200);
            }else{
                // response no found
                return response()->json([
                    "Photo" => 'No Data'
                ],400);
            }

        }catch(Throwable $ex)
        {
            // response error
            return array('Massage'=>$ex->getMessage());
        }
    }
    // function photo find by extension 
    public function photosFindByExtensions(Request $request,$extensions){
        try{
            //get data from token
            $userId = $this->getToken($request);
            // find userID against extension and pick image from Photo table
            $PhotosExist = Photo::where('user_id', $userId)->where('extension', $extensions)->pluck('image');
            //check photo 
            if (isset($PhotosExist)) {
            //response success
                return response()->json([
                    "Photo" => $PhotosExist
                ],200);
            }else{
            //response no found
                return response()->json([
                    "Photo" => 'No Data'
                ],400);
            }
        }catch(Throwable $ex)
        {
            //response error
            return array('Massage'=>$ex->getMessage());
        }
    }
    // function photo find by privacy 
    public function photosFindByPrivacy(Request $request,$privacy){
        try{
            // get data from token
            $userId = $this->getToken($request);
            // find userID against privacy and pick image from Photo table
            $PhotosExist = Photo::where('user_id', $userId)->first()->orwhere('privacy', $privacy)->pluck('image');
             if (isset($PhotosExist)) {
                //response success
                return response()->json([
                    "Photo" => $PhotosExist
                ],200);
            }else{
                //response no found
                return response()->json([
                    "Photo" => 'No Data'
                ]);
            }

        }catch(Throwable $ex)
        {
            //response no found
            return array('Massage'=>$ex->getMessage());
        }
    }
    // function photo find by time 
    public function photosFindByTime(Request $request,$time){
        try{
            //get data from token
            $userId = $this->getToken($request);
            // find userID against created_at and pick image from Photo table
            $PhotosExist = Photo::where('user_id', $userId)->where('created_at', $time)->pluck('image');
            //check photo    
            if (isset($PhotosExist)) {
                //response success
                return response()->json([
                    "Photo" => $PhotosExist
                ],200);
            }else{
                // response no found
                return response()->json([
                    "Photo" => 'No Data'
                ]);
            }

        }catch(Throwable $ex)
        {
            // response error
            return array('Massage'=>$ex->getMessage());
        }
    }
    // function send link create data  
    public function sendLinkDataSaveInDB($userId,Request $request){
        // create data
        $data=PhotoPrivacy::create([
            'user_id' => $userId,
            'email' => $request->email,
            'image_id' => $request->image_id,
        ]);
        // data
        return $data;
    }
    // function Send Photo Link Privacy
    protected function sendPhotoLinkPrivacy(PhotoSendLinkPrivacyRequest $request)
    {
        // get data from token
        $userId = $this->getToken($request);       
        try{
            // find sender id from User table
            $userSender= User::where('id', $userId)->first();
            // find receiver email from User table
            $userReceiver= User::where('email', $request->email)->first();
            // check sender email equle ReceiverEmail
            if($userSender->email==$userReceiver->email){
                return response()->json(["message" => "Email not send link"], 400);
            }
            // check receiver email
            if(isset($userReceiver)){
                // create data
                $data= $this->sendLinkDataSaveInDB($userId,$request);
                // save data
                $data->save();
                // a link with two parameter
                $url =url('api/photoLink/'.$request['email'].'/imageId/'.$request['image_id']);
                // send a mail Receiver
                Mail::to($request->email)->send(new ResetPasswordMail($url,'khawars282@gmail.com'));
            
            }else{
                // response no found
                return response()->json(['message' => "Email could not be sent to this email address"], 404);
            }
            // response success
            $response = ['message' => 'sent to this email address'];
                return response()->json($response, 200);
        }catch(Throwable $ex){
            // response error
            return array('Massage'=>$ex->getMessage());
        }
    }
    // function show data Receiver link
    protected function photoLink(Request $request,$email,$image_id){
        // get data from token
        $userId = $this->getToken($request);
        try{
            // find login user id from table User
            $userLogin= User::where('id', $userId)->first();
            // check login user not
            if(!isset($userLogin)){
                // response no found
                return response()->json(["message"=>"login first"],400);
                }
            // find user by email from table User 
            $userExist= User::where('email', $email)->first();
            // check user
            if(!isset($userExist)){
                // response no found
                return response()->json(["message"=>"User not exist"],400);
            }
            //find userRecever->email against image_id from table PhotoPrivacy
            $photoRecever =PhotoPrivacy::where('email', $userExist->email)->where('image_id', $image_id)->first();
            // check photo recever not
            if(!isset($photoRecever)){
                // response no found
                return response()->json(["message"=>"Photo not exist"],400);
                }
            //find userRecever->user_id against id of image from table PhotoPrivacy
            $photoExist =Photo::where('user_id', $photoRecever->user_id)->where('id', $image_id)->first();
            /* check login equle any email
                            AND
                check photo privacy equle public*/
            if($userLogin->email == $email && $photoExist->privacy == 'public'){
                // response success
                return response()->json(['Photo'=>$photoExist->image],200);
                /* check login equle receiver email
                             AND
                check photo privacy equle private*/
            }else if($userLogin->email == $photoRecever->email && $photoExist->privacy == 'private'){
                // response success
                return response()->json(['Photo'=>$photoExist->image],200);
            }else{
                // response no found
                return response()->json(['message' => "do not allow"],400);
            }
                // response no found
                return response()->json(['message' => "Email and Token could not"],400);
        }catch(Throwable $ex){
                // response error
            return array('Massage'=>$ex->getMessage());
        }
        
        
    }
    // function change privacy on send link by admin
    protected function photoLinkAccessPrivacy(Request $request,$email,$image_id,$privacy)
    {
        // get data from token   
        $userId = $this->getToken($request);
        try{
            // make array 
            $array = array('public', 'private');
            // check privacy 'public', 'private' not
            if($array[0]!=$privacy &&  $array[1]!=$privacy){
                // response privacy
                return response()->json(["message"=>"only give privacy ".$array[0].' : '.$array[1]],400);
            }
            // find id of user from table User
            $userLogin= User::where('id', $userId)->first();
            // check login user not
            if(!isset($userLogin)){
                // response no access
                return response()->json(["message"=>"login first"],400);
            }
            // find email of user or receiver from table User
            $userExist= User::where('email', $email)->first();
            // check  user  not
            if(!isset($userExist)){
                // response not found
                return response()->json(["message"=>"User not found"],400);
            }
            /* find loginId against user or receiver email against imageID from table PhotoPrivacy*/
            $photoAdmin =PhotoPrivacy::where('user_id', $userLogin->id)->where('email', $userExist->email)->where('image_id', $image_id)->first();
            // check admin not
            if(!isset($photoAdmin)){
                // response no access
                return response()->json(["message"=>"only admin access"],400);
            }
            // find admin user_id against id of image from table Photo
            $photoExist =Photo::where('user_id', $photoAdmin->user_id)->where('id', $image_id)->first();
            /* check login equle admin id
                             AND
                check photo*/
            if($userLogin->id == $photoAdmin->user_id && isset($photoExist)){
                // change privacy
                $photoExist->privacy = $privacy;
                // save privacy
                $photoExist->save();
                // response success
                return response()->json(['Photo'=>$photoExist->image,'privacy'=>'Privacy Change Successfully '],200);
            }else{
                // response no allow
                return response()->json(['privacy'=>$photoExist->privacy,'message' => "Privacy Change do not allow you"],400);
            }
            // response no found
            return response()->json(['message' => "Email and Token could not"],400);
            
        }catch(Throwable $ex){
            // response error
            return array('Massage'=>$ex->getMessage());
        }
        
        
    }
}
