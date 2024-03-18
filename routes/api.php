<?php
use App\Models\User;
use App\Models\Haves;
use App\Models\Notes;
use App\Models\Eleves;
use App\Models\Events;
use App\Mail\HelloMail;
use App\Models\Classes;
use App\Models\Teaches;
use App\Models\Services;
use App\Models\Actualite;
use App\Models\Exercices;
use App\Models\Notification;
use Illuminate\Http\Request; 
use App\Models\notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\MailController;
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/auth', function (Request $request) {
    $email = $request->input('email');
    $password = $request->input('password');

    $user = User::with('role')
        ->where('email', $email)
        ->first();

    if ($user) {
        $user->token = $request->input('token');
        $user->save();
        
        if (password_verify($password, $user->password)) {
            switch ($user->role->name) {
                case 'admin':
                    return response()->json(['redirect_url' => 'admin'], 200);
                    break;
                case 'parent':
                    return response()->json(['redirect_url' => 'parent'], 200);
                    break;
                case 'enseignant':
                    return response()->json(['redirect_url' => 'enseignant'], 200);
                    break;
                default:
                    return response()->json(['message' => 'Unknown role'], 401);
                    break;
            }
        } else {
            return response()->json(['message' => 'Invalid password'], 401);
        }
    } else {
        return response()->json(['message' => 'User not found'], 404);
    }
});
Route::post('/newpass', function (Request $request) {
    $password = $request->input('password');
    $email = $request->input('email');
    $user = User::where('email', $email)->first(); 
    if ($user) {
        $user->password = $password;
        $user->save();
        return response()->json(['message' => 'Password reset successfully'], 200);
    }
    return response()->json(['message' => 'Failed to reset password'], 500);
}); 
Route::get('/getActualites', function () {
    
    $actualites = DB::table('actualites')
        ->join('users', 'users.id', '=', 'actualites.user_id')
        ->orderBy('actualites.created_at', 'desc')
        ->select('actualites.file_path','actualites.id', 'actualites.body', 'actualites.created_at', 'users.name as userName','users.avatar as avatar')
        ->get();  

    return response()->json(['list' => $actualites], 200);
}); 

Route::post ('/respass',[MailController::class,'maildata'])->name('send_mail');
Route::post('/addActualite', function (Request $request) {
    $body = $request->input('body');
    $email = $request->input('email');
    $user = User::where('email', $email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    

    $created_at = now();

    $actualite = new Actualite();
    $actualite->body = $body;
    $actualite->file_path = $request->input('file');
    $actualite->user_id = $user->id;
    $actualite->created_at = $created_at;

    if ($actualite->save()) {
        return response()->json(['message' => 'Actualite added successfully'], 200);
    } else {
        return response()->json(['message' => 'Failed to add actualite'], 500);
    }
});
Route::get('/getActualite/{id}', function ($id) {
    $actualite = DB::table('actualites')
        ->join('users', 'users.id', '=', 'actualites.user_id')
        ->where('actualites.id', $id)
        ->select('actualites.file_path', 'actualites.id', 'actualites.body', 'actualites.created_at', 'users.name as userName')
        ->get();  

    return response()->json(['actualite' => $actualite], 200);
});
Route::delete('/deleteActualite/{id}', function($id) {
    try {
        $actualite = Actualite::findOrFail($id); 
        $actualite->delete();
        return response()->json(['message' => 'Actualite deleted successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete actualite', 'message' => $e->getMessage()], 500);
    }
});
Route::put('/updateActualite/{id}',function(Request $request,$id){
    $actualite = Actualite::findOrFail($id);
    $actualite->body=$request->input('body');
    $actualite->file_path=$request->input('file');
    $actualite->updated_at=now();
    $actualite->save(); 

        return response()->json(['message' => 'Actualite updated successfully']);
});
Route::get('/getUser/{email}',function($email){ 
    $user = User::where('email', $email)->first();
    if ($user) {
       
        return response()->json(['user' => $user], 200);
    } else { 
       
        return response()->json(['error' => 'User not found'], 404);
    }
});
Route::put('/updateUser', function (Request $request) {
    $user = User::where('email', $request->input('email'))->first();
    if($request->input('password')){
    $user->password = $request->input('password');}
    $user->avatar = $request->input('file');
    $user->phone = $request->input('phone'); 
    $user->address = $request->input('address'); 
    $user->save();

    return response()->json(['message' => 'User updated successfully'], 200);
});
Route::get('/getParents',function(){ 
    $parents=User::where('role_id',2)->get();
    return response()->json(['list'=>$parents],200);
}); 
Route::delete('/deleteParent/{email}', function($email) {
    try {
        $parent = User::where('email',$email)->first(); 
        $parent->delete();
        return response()->json(['message' => 'parent deleted successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete parent', 'message' => $e->getMessage()], 500);
    }
}); 
Route::post('/addParent', function(Request $request) {
    
    $user = new User(); 
    $user->role_id = 2;
    $user->name = $request->input('name');
    $user->token = $request->input('token');
    $user->email = $request->input('email');
    $user->password = $request->input('password'); 
    $user->avatar = $request->input('file');
    $user->address = $request->input('address'); 
    $user->phone = $request->input('phone');
    $user->save();

    
    Mail::to($user->email)->send(new HelloMail($user->name, 'firassayeb2@gmail.com', 'Welcome!', 'Your account has been created. Here are your login credentials: Email: '.$user->email.' Password: '.$request->input('password')));

    return response()->json(['message' => 'Parent added successfully and email sent.'], 200);
});
Route::post('/addClasse', function(Request $request) {
    
    $classe = new Classes(); 
    $classe->name=$request->input('body');
    $classe->emploi=$request->input('file');
    $classe->examens=$request->input('examen');
    $classe->save();
    return response()->json(['message' => 'Classe added successfully .'], 200);
});
Route::get('/getClasses', function () {
 
       $classes =Classes::all();  

    return response()->json(['list' => $classes], 200);
});  
Route::delete('/deleteClasse/{name}', function($name) {
    try {
        $classe = Classes::where('name',$name)->first(); 
        $classe->delete();
        return response()->json(['message' => 'classe deleted successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete classe', 'message' => $e->getMessage()], 500);
    }
}); 
Route::get('/getClasse/{id}',function($id){ 
    $classe = Classes::where('id', $id)->first();
    if ($classe) { 
       
        return response()->json(['classe' => $classe], 200);
    } else { 
       
        return response()->json(['error' => 'classe not found'], 404);
    }
});
Route::put('/updateClasse/{id}',function(Request $request,$id){
    $classe = Classes::findOrFail($id);
    if($request->input('body')){
        $classe->name=$request->input('body');
    }
    if($request->input('file')){
        $classe->emploi=$request->input('file');
    } 
    if($request->input('examens')){
        $classe->examens=$request->input('examens');
    }
    $classe->updated_at=now();  
    $classe->save(); 

        return response()->json(['message' => 'Classe updated successfully'],200);
});
Route::get('/getEleves', function () {
    $eleves = Eleves::with('class', 'parents')->get();

    $transformedEleves = $eleves->map(function ($eleve) {
        return [
            'id' => $eleve->id,
            'profil'=> $eleve->profil,
            'name' => $eleve->name, 
            'lastname' => $eleve->lastname,
            'date_of_birth'=>$eleve->date_of_birth,
            'class_name' => $eleve->class->name,
            'parent_names' => $eleve->parents->pluck('name')->toArray(),
        ];
    });

    return response()->json(['list' => $transformedEleves], 200);
});

Route::post('/addEleve',function(Request $request){
    $eleve=new Eleves();
    $eleve->name=$request->input('name');
    $eleve->profil=$request->input('file');
    $eleve->lastname=$request->input('lastname');
    $eleve->date_of_birth=$request->input('date');
    $eleve->class_id=Classes::where('name',$request->input('class'))->first()->id;
    $eleve->save();
    $parent1Email = $request->input('parent1'); 
    $parent2Email = $request->input('parent2'); 

    
    $parent1Id = User::where('email', $parent1Email)->value('id');
    $parent2Id = User::where('email', $parent2Email)->value('id');

    
    if ($parent1Id) {
        $eleveUser1 = new Haves();
        $eleveUser1->eleve_id = $eleve->id;
        $eleveUser1->user_id = $parent1Id; 
        $eleveUser1->save();
    }

    if ($parent2Id) {
        $eleveUser2 = new Haves(); 
        $eleveUser2->eleve_id = $eleve->id;
        $eleveUser2->user_id = $parent2Id;
        $eleveUser2->save();
    }
    return response()->json(['message' => 'eleve added successfully'],200);

});
Route::delete('/deleteEleve/{name}', function($name) {
    try {
        $eleve = Eleves::where('name',$name)->first(); 
        $eleve->delete();
        return response()->json(['message' => 'eleve deleted successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete eleve', 'message' => $e->getMessage()], 500);
    }
});
Route::get('/getEleve/{id}', function($id) {
    $eleve = Eleves::with('class', 'parents')->find($id);

    if ($eleve) {
        $transformedEleve = [
            'id' => $eleve->id,
            'profil'=> $eleve->profil,
            'name' => $eleve->name, 
            'lastname' => $eleve->lastname,
            'date_of_birth' => $eleve->date_of_birth,
            'class_name' => $eleve->class->name,
            'parent_names' => $eleve->parents->pluck('email')->toArray(),
        ];

        return response()->json(['eleve' => $transformedEleve], 200);
    } else {
        return response()->json(['error' => 'Eleve not found'], 404);
    }
});

Route::put('/updateEleve/{id}', function (Request $request, $id) {
    $eleve = Eleves::findOrFail($id);

    if ($request->has('name')) {
    $eleve->name = $request->input('name');}
    if ($request->has('lastname')) {
    $eleve->lastname = $request->input('lastname');}
    if ($request->has('date')) {
    $eleve->date_of_birth = $request->input('date');}
    if ($request->has('file')) {
    $eleve->profil = $request->input('file');}
   
    if ($request->has('class')) {
        $className = $request->input('class');
        $class = Classes::where('name', $className)->first();
        if ($class) {
            $eleve->class_id = $class->id;
        }
    }
         
     
    $eleve->save();

    
    $parent1Email = $request->input('parent1');
    $parent2Email = $request->input('parent2');

   
    $parent1Id = User::where('email', $parent1Email)->value('id');
    $parent2Id = User::where('email', $parent2Email)->value('id');

     
    $parentsToSync = []; 
    if ($parent1Id) {
        $parentsToSync[$parent1Id] = ['created_at' => now(), 'updated_at' => now()];
    }
    if ($parent2Id) {
        $parentsToSync[$parent2Id] = ['created_at' => now(), 'updated_at' => now()];
    }
    $eleve->parents()->sync($parentsToSync);

    return response()->json(['message' => 'eleve updated successfully'], 200);
});
Route::get('/getEnseignants',function(){ 
    $parents=User::where('role_id',3)->get();
    return response()->json(['list'=>$parents],200);
});
Route::post('/addEnseignant', function(Request $request) {
    
    $user = new User(); 
    $user->role_id = 3; 
    $user->name = $request->input('name');
    $user->email = $request->input('email');
    $user->token = $request->input('token');
    $user->password = $request->input('password'); 
    $user->avatar = $request->input('file');
    $user->address = $request->input('address'); 
    $user->phone = $request->input('phone');
    $user->save(); 
    $classList = explode(',', $request->input('list'));

foreach ($classList as $className) {
    $class = Classes::where('name', $className)->first();
    if ($class) {
        $teaches = new Teaches();
        $teaches->user_id = $user->id;
        $teaches->class_id = $class->id;
        $teaches->save();
    } else {
        return response()->json(['message' => 'Class not found: ' . $className], 404);
    }
}


    Mail::to($user->email)->send(new HelloMail($user->name, 'firassayeb2@gmail.com', 'Welcome!', 'Your account has been created. Here are your login credentials: Email: '.$user->email.' Password: '.$request->input('password')));

    return response()->json(['message' => 'Enseignant added successfully and email sent.'], 200);
});
Route::delete('/deleteEnseignant/{email}', function($email) {
    try {
        $parent = User::where('email',$email)->first(); 
        $parent->delete(); 
        return response()->json(['message' => 'parent deleted successfully'], 200);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Failed to delete parent', 'message' => $e->getMessage()], 500);
    }
});
Route::put('/updateEnseignant', function(Request $request) {
    try {
       
        $user = User::where('email', $request->input('email'))->first();
        if ($request->has('password')) {
            $user->password = bcrypt($request->input('password'));
        }
        if ($request->has('file')) {
            
            $user->avatar = $request->input('file');
        }
        $user->phone = $request->input('phone');
        $user->address = $request->input('address');
        $user->save(); 
        $classesToSync = []; 
        
            $classList = explode(',', $request->input('list'));
            foreach ($classList as $className) {
                $class = Classes::where('name', $className)->first();
               
                     $classesToSync[$class->id] = ['created_at' => now(), 'updated_at' => now()];
            } 
        $user->classes()->sync($classesToSync);        
        return response()->json(['message' => 'Enseignant updated successfully'], 200);
    } catch (Exception $e) {
       
        return response()->json(['message' => 'Failed to update Enseignant', 'error' => $e->getMessage()], 500);
    }
}); 
Route::get('/getUsers',function(){
   $list=User::all();
   return response()->json(['list'=>$list],200);
});
Route::post('/addNotification', function(Request $request) {
    $user = User::where('email', $request->input('email'))->first(); 
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }
 
    $notification = new Notification();
    $notification->user_id = $user->id; 
    $notification->body = $request->input('message');
    $notification->save();
    
    $usersList =explode(',', $request->input('list'));   

    foreach ($usersList as $userName) {
        $destUser = User::where('email', $userName)->first();
        if ($destUser) {  
            DB::table('notification_user')->insert([
                'notification_id' => $notification->id,
                'user_id' => $destUser->id
            ]); 
            
        } else {
            return response()->json(['message' => 'User not found: ' . $userName], 404);
        }
    } 

    if ($notification->exists) {
        return response()->json(['message' => 'Notification added successfully'], 200);
    } else {
        return response()->json(['message' => 'Failed to add notification'], 500);
    }
});
Route::get('/getNotifications',function(){
   $not=Notification::all();
   return response()->json(['list'=>$not],200);
});
Route::get('/getNotification',function(){
    $not=DB::table('notification_user')->get();
    return response()->json(['list'=>$not],200);
 });
Route::get('/getServices',function(){
    $list=Services::all();
    return response()->json(['list'=> $list],200);
});
Route::post('/addService',function(Request $request){
    $service=new Services();
    $service->name=$request->input('name');
    $service->price=$request->input('price');
    $service->save();
    return response()->json(['message'=>'service added successfully'],200); 
});
Route::get('/getService/{name}',function($name){
    $service = Services::where('name', $name)->first();
    return response()->json(['service'=>$service],200);
});
Route::delete('/deleteService/{name}', function($name) {
    $service = Services::where('name', $name)->first();
    if ($service) {
        $service->delete();
        return response()->json(['message' => 'Service deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'Service not found'], 404);
    } 
});

Route::put('/updateService/{name}', function($name, Request $request) {
    $service = Services::where('name', $name)->first();
    if ($service) {
        if ($request->input('name')) { 
            $service->name = $request->input('name');
        }
        if ($request->input('price')) {
            $service->price = $request->input('price');
        }
        $service->save(); 
        return response()->json(['message' => 'Service modified successfully'], 200);
    } else {
        return response()->json(['message' => 'Service not found'], 404);
    }
});
Route::get('/getEvents',function(){
    $list=Events::all();
    return response()->json(['list'=> $list],200);
}); 
Route::post('/addEvent',function(Request $request){
    $event=new Events(); 
    $event->name=$request->input('name');
    $event->price=$request->input('price');
    $event->date=$request->input('date');
    $event->save();
    return response()->json(['message'=>'event added successfully'],200); 
}); 
Route::get('/getEvent/{name}',function($name){
    $event = Events::where('name', $name)->first();
    return response()->json(['event'=>$event],200);
});
Route::delete('/deleteEvent/{name}', function($name) {
    $event = Events::where('name', $name)->first();
    if ($event) {
        $event->delete();
        return response()->json(['message' => 'Event deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'event not found'], 404);
    } 
});
Route::put('/updateEvent/{name}', function($name, Request $request) {
    $event = Events::where('name', $name)->first();
    if ($event) { 
        if ($request->input('name')) { 
            $event->name = $request->input('name');
        }
        if ($request->input('price')) {
            $event->price = $request->input('price');
        }
         if ($request->input('date')) {
            $event->date = $request->input('date');
        }
        $event->save();  
        return response()->json(['message' => 'Event modified successfully'], 200);
    } else {
        return response()->json(['message' => 'Event not found'], 404);
    }
});
Route::get('/getExercices/{name}', function($name) {
    $class = Classes::where('name', $name)->first();
    
    if ($class) {  
        $exercises = Exercices::where('class_id', $class->id)->get();
        return response()->json(['list' => $exercises], 200);
    } else {
        return response()->json(['message' => 'Class not found'], 404);
    }
});
  
Route::post('/addExercice',function(Request $request){
    $exercice=new Exercices(); 
    $exercice->name=$request->input('name');  
    $exercice->description=$request->input('description');
    if ($request->input('class')) {
        $className = $request->input('class');
        $class = Classes::where('name', $className)->first();
        if ($class) {
            $exercice->class_id = $class->id;
        } else {
           
        }
    }
    
    $exercice->save();
    $user = User::where('email', $request->input('email'))->first();
    $notification = new Notification();
    $notification->user_id = $user->id; 
    $notification->body =$exercice->name;
    $notification->save(); 
    $destUsers=$class->eleves->flatMap(function ($student) {
        return $student->parents;
    });
    foreach($destUsers as $destUser){
        DB::table('notification_user')->insert([
            'notification_id' => $notification->id,
            'user_id' => $destUser->id
        ]); 
    }
     
    return response()->json(['message'=>'exercice added successfully'],200); 
}); 
Route::get('/getExercice/{name}',function($name){
    $event = Exercices::where('name', $name)->first();
    return response()->json(['exercice'=>$event],200);
});
Route::delete('/deleteExercice/{name}', function($name) {
    $event = Exercices::where('name', $name)->first();
    if ($event) { 
        $event->delete();
        return response()->json(['message' => 'exercice deleted successfully'], 200);
    } else {
        return response()->json(['message' => 'exercice not found'], 404);
    } 
});
Route::put('/updateExercice/{name}', function($name, Request $request) {
    $event = Exercices::where('name', $name)->first();
    if ($event) { 
        if ($request->input('name')) { 
            $event->name = $request->input('name');
        } 
        if ($request->input('description')) {
            $event->description = $request->input('description');
        }
       
       
        $event->save();  
        return response()->json(['message' => 'exercice modified successfully'], 200);
    } else { 
        return response()->json(['message' => 'exercice not found'], 404);
    }
});
Route::get('/getUsers/{name}', function($name) {
     
    $class = Classes::where('name', $name)->first();

    if ($class) {
        
        $students = $class->eleves;

        
        $parents = $students->flatMap(function ($student) {
            return $student->parents;
        });

        
        return response()->json(['list'=>$parents],200); 
    } else {
         
        return response()->json(['message' => 'Class not found'], 404);
    }
});
Route::get('/getEleves/{name}', function($name) {
    $class = Classes::where('name', $name)->first();
    
    if ($class) {
        $eleves = Eleves::where('class_id', $class->id)->get();
        return response()->json(['eleves' => $eleves], 200);
    } else {
        return response()->json(['message' => 'Class not found'], 404);
    }
});
Route::get('/getParents/{name}', function($name) {
    $eleve=Eleves::where('name',$name)->first();
    if($eleve){
        $parents=$eleve->parents;
    }
    return response()->json(['list' => $parents], 200);
});
 
Route::post('/addNote', function(Request $request) {
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        
        $request->validate([
            'file' => 'required|mimes:xlsx,xls', 
        ]);

        $data = Excel::toArray([], $file);

        $success = true;

        foreach ($data[0] as $row) { 
            $eleve_id = $row[0] ?? null;
            $matiere = $row[1] ?? null;
            $note = $row[2] ?? null;
         
            if ($eleve_id !== null && $matiere !== null && $note !== null) {
                error_log($eleve_id);
                error_log($matiere);
                error_log($note);
                
                try {
                    Notes::create([
                        'eleve_id' => $eleve_id,
                        'matiere' => $matiere,
                        'note' => $note,
                    ]); 
                    error_log('success');
                } catch (\Exception $e) {
                    error_log('Error creating Notes instance: ' . $e->getMessage());
                    $success = false;
                }
            } else {
                // Handle missing or invalid data
                $success = false;
            }
            
        }
        if ($success) {$user = User::where('email', $request->input('email'))->first();
            $notification = new Notification();
            $notification->user_id = $user->id; 
            $notification->body ="Notes Sont Disponibles ";
            $notification->save();
            $parents=User::where('role_id',2)->get();
            foreach($parents as $parent){
                DB::table('notification_user')->insert([
                    'notification_id' => $notification->id,
                    'user_id' => $parent->id 
                ]); 
            }
            return response()->json(['message' => 'File uploaded and processed successfully'], 200);
        } else {
            return response()->json(['error' => 'Some rows could not be processed.'], 400);
        }
    } else {
        return response()->json(['error' => 'No file uploaded'], 400);
    }
});
Route::get('/getParents',function(){
    $parents=User::where('role_id',2)->get();
    return response()->json(['list'=>$parents],200);
});   
Route::get('/getFils/{email}', function($email) {
   
    
    $parent = User::where('email', $email)->first();

    if ($parent) {
        
        $haves = Haves::where('user_id', $parent->id)->get();
        
        
        $eleves = [];
    
       
        foreach ($haves as $have) {
            
            $eleve = Eleves::find($have->eleve_id);
            
            
            if ($eleve) {
                $eleves[] = $eleve;
            }
        }

        
        return response()->json(['list' => $eleves]);
    } else {
        
        return response()->json(['error' => 'User not found'], 404);
    }
});
Route::get('/getNotes/{id}',function($id){
    $eleve=Eleves::find($id);
    $notes=[];
    if($eleve){
    $notts=Notes::where('eleve_id',$id)->get();
    foreach($notts as $n){
      $notes[]=$n;
    }
    return response()->json(['list' => $notes]);
    } else {
        
        return response()->json(['error' => 'Eleve not found'], 404);
    }
}); 
