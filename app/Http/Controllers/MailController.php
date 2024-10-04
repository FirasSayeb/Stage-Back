<?php

namespace App\Http\Controllers;

use App\Mail\HelloMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    //
    public function mailform()
{
return view('name');
}
public function maildata(Request $request)
{
$name = "app";
$email = $request->email;  
$sub = "Reset Password";
$verificationCode = random_int(100000, 999999); 
$mess = "Your verification code: " . $verificationCode; 
$mailData = [ 
'url' => 'https://sandroft.com/',
];
$send_mail = "";
try{
    Mail::to($email)->send(new HelloMail($name, $send_mail, $sub, $mess));
    /*return view('name',[
        'name'=>$name,
        'mess'=>$mess
    ]);*/
    return response()->json(['code'=>$verificationCode],200);
}catch(Exception $e){
    return response()->json(['code'=>0],404);
}
 
}
}
