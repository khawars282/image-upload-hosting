<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Controllers\PhotoController;


Route::get('/send_photo_link_privacy', [PhotoController::class, 'sendPhotoLinkPrivacy']);
Route::get('/PhotoLinkAccessPrivacy/{email}/{token}', [PhotoController::class, 'photoLinkAccessPrivacy']);

Route::group(['middleware' => ['verification']], function() {

    //Photo
   
    Route::post('/photo', [PhotoController::class, 'uploadPhoto']);
    Route::delete('/remove_photo/{id}', [PhotoController::class, 'removePhoto']);
    Route::get('/list_all_Photos', [PhotoController::class, 'listAllPhotos']);

    Route::get('/photos_find_by_name/{name}', [PhotoController::class, 'photosFindByName']);
    Route::get('/photos_find_by_extensions/{extension}', [PhotoController::class, 'photosFindByExtensions']);
    Route::get('/photos_find_by_privacy/{privacy}', [PhotoController::class, 'photosFindByPrivacy']);
    Route::get('/photos_find_by_time/{created_at}', [PhotoController::class, 'photosFindByTime']);
    
    Route::get('/send_photo_link_privacy', [PhotoController::class, 'sendPhotoLinkPrivacy']);
    Route::get('/photoLink/{email}/imageId/{image_id}', [PhotoController::class, 'photoLink']);
    Route::get('/photoLink/{email}/imageId/{image_id}/privacy/{privacy}', [PhotoController::class, 'photoLinkAccessPrivacy']);

});
