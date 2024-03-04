<?php

use App\Models\User;
use App\Models\Actualite;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MailController;

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
    return view('welcome');
});
Route::get('/users',function(){
    $users=User::all();
     return view('home',[
        'users'=>$users
     ]);
});
Route::get('/getActualites',function(){
    $actualites=Actualite::all();
    return response()->json(['list'=>$actualites],200);
  });

Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
});

