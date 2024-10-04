<?php
use App\Models\Absence;
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

    $user = User::where('email', $email)
        ->first();

    if ($user) {    
        $user->token = $request->input('token');
        $user->save();
        
        if (password_verify($password, $user->password)) {
            switch ($user->role_id) {
                case 1:
                    return response()->json(['redirect_url' => 'admin'], 200);
                    break;
                case 2:
                    return response()->json(['redirect_url' => 'parent'], 200);
                    break;
                case 3:
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
    $actualite->user_id = $user->id;
    $actualite->created_at = $created_at;
    $file = $request->file('file');

    if ($file) {
        
        $filePath = $file->store('public');
    
        
        $actualite->file_path = $filePath;
    
        
        $actualite->save();
    } else { 
       
    }
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
Route::post('/updateActualite/{id}', function(Request $request, $id) {
    try {
        
        $actualite = Actualite::findOrFail($id);
         
       
        if ($request->has('body')) {
            $actualite->body = $request->input('body');
        }
        
        
        if ($request->file('file')) {
           
            $filePath = $request->file('file')->store('public');

            
            $actualite->file_path = $filePath;
        } else {
           
        }

        
        $actualite->save();

        return response()->json(['message' => 'Actualite updated successfully']);
    } catch (Exception $e) {
       
        return response()->json(['error' => 'Failed to update actualite', 'error' => $e->getMessage()], 500);
    }
});


Route::get('/getUser/{email}',function($email){ 
    $user = User::where('email', $email)->first();
    if ($user) {
       
        return response()->json(['user' => $user], 200);
    } else { 
       
        return response()->json(['error' => 'User not found'], 404);
    }
});
Route::post('/updateUser',  function (Request $request)
{
    $user = User::where('email', $request->input('email'))->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    if ($request->filled('password')) {
        $user->password = bcrypt($request->input('password'));
    }

    
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $filePath = $file->store('public'); 
        $user->avatar = $filePath;
    }

    if ($request->filled('phone')) {
        $user->phone = $request->input('phone');
    }

    if ($request->filled('address')) {
        $user->address = $request->input('address');
    }

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
    if ($request->hasFile('file')) {
        $filePath = $request->file('file')->store('public');
        $user->avatar = $filePath;
    }
    $user->address = $request->input('address'); 
    $user->phone = $request->input('phone');
    $user->save();

    
    Mail::to($user->email)->send(new HelloMail($user->name, '', 'Welcome!', 'Your account has been created. Here are your login credentials: Email: '.$user->email.' Password: '.$request->input('password')));

    return response()->json(['message' => 'Parent added successfully and email sent.'], 200);
});
Route::post('/addClasse', function(Request $request) {
    
    $classe = new Classes();
     
   
    $classe->name = $request->input('name'); 
    
    if ($request->hasFile('file')) {
        $filePath1 = $request->file('file')->store('public');
        $classe->emploi = $filePath1;
    }
    
    if ($request->hasFile('examens')) {
        $filePath2 = $request->file('examens')->store('public');
        $classe->examens = $filePath2;
    }

    
    $classe->save();

    
    return response()->json(['message' => 'Class added successfully.'], 200);
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
Route::post('/updateClasse/{id}',function(Request $request,$id){
    $classe = Classes::findOrFail($id);
    if($request->input('body')){
        $classe->name=$request->input('body');
    }
    if ($request->hasFile('file')) {
        $filePath1 = $request->file('file')->store('public');
        $classe->emploi = $filePath1;
    }
    
    if ($request->hasFile('examens')) {
        $filePath2 = $request->file('examens')->store('public');
        $classe->examens = $filePath2;
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
            'num'=>$eleve->num,
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
    $eleve->num=$request->input('num');
    if ($request->hasFile('file')) {
        $filePath = $request->file('file')->store('public');
        $eleve->profil = $filePath;
    }

    $eleve->lastname=$request->input('lastname');
    $eleve->date_of_birth=$request->input('date');
    $eleve->class_id=Classes::where('name',$request->input('class'))->first()->id;
    $eleve->save();
    $List = explode(',', $request->input('list'));
    foreach($List as $parent){
    $parent1Email = $parent;
    $parent1Id = User::where('email', $parent1Email)->value('id'); 
    $eleveUser1 = new Haves();
        $eleveUser1->eleve_id = $eleve->id;
        $eleveUser1->user_id = $parent1Id; 
        $eleveUser1->save();
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
            'num'=>$eleve->num,
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

Route::post('/updateEleve/{id}', function (Request $request, $id) {
    $eleve = Eleves::findOrFail($id);
    if ($request->has('num')) {
        $eleve->num = $request->input('num');}
    if ($request->has('name')) {
    $eleve->name = $request->input('name');}
    if ($request->has('lastname')) {
    $eleve->lastname = $request->input('lastname');}
    if ($request->has('date')) {
    $eleve->date_of_birth = $request->input('date');}
    if ($request->hasFile('file')) {
        $filePath1 = $request->file('file')->store('public');
        $eleve->profil = $filePath1;
    }
   
    if ($request->has('class')) {
        $className = $request->input('class');
        $class = Classes::where('name', $className)->first();
        if ($class) {
            $eleve->class_id = $class->id;
        }
    }
         
     
    $eleve->save(); 
    if ($request->input('list') != "") {
        $List = explode(',', $request->input('list'));
    
        if ($List) {
            $parentsToSync = [];
    
            foreach ($List as $parent) {
                $parent1Email = $parent;
                $parent1Id = User::where('email', $parent1Email)->value('id');
    
                if ($parent1Id !== null && $parent1Id !== '') {
                    $parentsToSync[$parent1Id] = ['created_at' => now(), 'updated_at' => now()];
                } else {
                    
                }
                
            }
            
            $eleve->parents()->sync($parentsToSync);
        }
    }
    
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
    $file = $request->file('file');
    
    if ($file) {
        
        $filePath = $file->store('public');
    
        
        $user->avatar = $filePath;
    
        
       
    } else { 
       
    }
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


    Mail::to($user->email)->send(new HelloMail($user->name, '', 'Welcome!', 'Your account has been created. Here are your login credentials: Email: '.$user->email.' Password: '.$request->input('password')));

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
Route::post('/updateEnseignant', function(Request $request) {
    try {
        $user = User::where('email', $request->input('email'))->first();
        
        
        if ($request->has('password')) {
            $user->password = bcrypt($request->input('password'));
        }
        
        
        if ($request->has('phone')) {
        $user->phone = $request->input('phone');}
        if ($request->has('address')) {
        $user->address = $request->input('address');}
        $file = $request->file('file');
        if ($file) {
            
            $filePath = $file->store('public');
        
            
            $user->avatar = $filePath;
        
            
           
        } else { 
             
        }
        $user->save();
        
        if ($request->has('list')) {
            $classList = $request->input('list');
            
           
            if (empty($classList)) {
               // $user->classes()->detach();
            } else {
               
                $classesToSync = [];
                foreach (explode(',', $classList) as $className) {
                    $class = Classes::where('name', $className)->first();
                    if ($class) {
                        $classesToSync[$class->id] = ['created_at' => now(), 'updated_at' => now()];
                    }
                }
                $user->classes()->sync($classesToSync);
            }
        }
        
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
    $service->description=$request->input('description');
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
        if ($request->input('description')) {
            $service->description = $request->input('description');
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
    $event->description=$request->input('description');
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
        if ($request->input('description')) {
            $event->description = $request->input('description');
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

        $parents=$parents->unique();
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
        Notes::truncate();
        foreach ($data[0] as $row) { 
            $eleve_num = $row[0] ?? null;
            $matiere = $row[1] ?? null;
            $note = $row[2] ?? null;
        
            if ($eleve_num !== null && $matiere !== null && $note !== null) {
                error_log($eleve_num);
                error_log($matiere);
                error_log($note);
                
                
                $student = Eleves::where('num', $eleve_num)->first();
        
                
                if ($student) {
                    try {
                        Notes::create([ 
                            'eleve_id' => $student->id,
                            'matiere' => $matiere,
                            'note' => $note,
                        ]); 
                        error_log('success');
                    } catch (\Exception $e) {
                        error_log('Error creating Notes instance: ' . $e->getMessage());
                        $success = false;
                    }
                } else {
                    error_log('Student not found for number: ' . $eleve_num);
                    $success = false;
                }
            } else {
               
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
Route::get('/getExer/{id}',function($id){
    $eleve=Eleves::find($id);
    $exercises=[];
    if($eleve){
    $exs=Exercices::where('class_id',$eleve->class_id)->get();
    foreach($exs as $ex){
      $exercises[]=$ex;
    }return response()->json(['list' => $exercises]);
} else {
    
    return response()->json(['error' => 'Eleve not found'], 404);
}
}); 
Route::get('/getNoti/{email}', function ($email) {
    $user = User::where('email', $email)->first();

    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }

    
    $notificationIds = DB::table('notification_user')->where('user_id', $user->id)->pluck('notification_id');

   
    $notifications = DB::table('notifications')  
    ->join('users', 'notifications.user_id', '=', 'users.id')
    ->whereIn('notifications.id', $notificationIds)
    ->orderBy('notifications.created_at', 'desc')
    ->select('notifications.*', 'users.name as sender_name')
    ->get();


    return response()->json(['list' => $notifications]);
});
Route::get('/getSer/{id}',function($id){
    $list=DB::table('demandser')->where('eleve_id',$id)->get();
    return response()->json(['list' => $list],200);
});
Route::post('/addSer',function(Request $request){
    $eleve=$request->input('eleve');
    $service=$request->input('service');
    error_log($eleve);
    error_log($service);
    DB::table('demandser')->insert([
        'eleve_id' => $eleve,
        'service_id' => $service
    ]); 
    return response()->json(['message' => 'Service added successfuly'],200);
});
Route::delete('/delSer/{id}', function($id) {
    DB::table('demandser')->where('eleve_id', $id)->delete();
    return response()->json(['message' => 'Service deleted successfully'], 200);
});
Route::get('/getEvt/{id}',function($id){
    $list=DB::table('demandevt')->where('eleve_id',$id)->get();
    return response()->json(['list' => $list],200);
});
Route::post('/addEvt',function(Request $request){
    $eleve=$request->input('eleve');
    $event=$request->input('event');
    error_log($eleve);
    error_log($event);
    DB::table('demandevt')->insert([
        'eleve_id' => $eleve,
        'event_id' => $event
    ]); 
    return response()->json(['message' => 'Event added successfuly'],200);
});
Route::delete('/delEvt/{id}', function($id) {
    DB::table('demandevt')->where('eleve_id', $id)->delete();
    return response()->json(['message' => 'Event deleted successfully'], 200);
}); 
Route::get('/getSer',function(){
    $list=DB::table('demandser')
    ->join('eleves','demandser.eleve_id','=','eleves.id')
    ->join('services','demandser.service_id','=','services.id')
    ->select('demandser.id','eleves.name as eleve_name','services.name as service_name')
    ->get();
    return response()->json(['list' => $list],200);
});
Route::delete('/delSe/{id}', function($id) {
    DB::table('demandser')->where('id', $id)->delete();
    return response()->json(['message' => 'Service deleted successfully'], 200);
});
Route::get('/getEvt',function(){
    $list=DB::table('demandevt')
    ->join('eleves','demandevt.eleve_id','=','eleves.id')
    ->join('events','demandevt.event_id','=','events.id')
    ->select('demandevt.id','eleves.name as eleve_name','events.name as event_name')
    ->get();
    return response()->json(['list' => $list],200);
});
Route::delete('/delEv/{id}', function($id) {
    DB::table('demandevt')->where('id', $id)->delete();
    return response()->json(['message' => 'event deleted successfully'], 200);
});
Route::get('/getUserss/{email}', function ($email) {
    
    $parent = User::where('email', $email)->first();

   
    $sons = $parent->sons;
    $classes = [];
    foreach ($sons as $son) {
        $classes = array_merge($classes, $son->class->pluck('id')->toArray());
    }

    
    $teachers = collect();
    foreach ($classes as $classId) {
        
        $classTeachers = Teaches::where('class_id', $classId)->get();
        foreach ($classTeachers as $classTeacher) {
            $teacher = User::find($classTeacher->user_id);
           
            $teachers->push($teacher);
        }
    }

    
    $admins = User::where('role_id', 1)->get();

    
$teachersArray = $teachers->toArray();
$adminsArray = $admins->toArray();
$mergedArray = array_merge($teachersArray, $adminsArray);


$mergedCollection = collect($mergedArray);

$uniqueTeachers = $mergedCollection->unique('id')->values();

return response()->json(['list' => $uniqueTeachers], 200);

});

Route::post('/marquerAbsence', function(Request $request) {
    
    $user = User::where('email', $request->input('email'))->first();
    if (!$user) {
        return response()->json(['error' => 'User not found'], 404);
    }
    
    $absenceList = json_decode($request->input('absenceList'), true);
    $class = Classes::where('name', $request->input('class'))->first();
    $eleves = Eleves::where('class_id', $class->id)->get();
for ($i = 0; $i < count($eleves); $i++) {
    $is_absent = isset($absenceList[$i]) ? $absenceList[$i] : false; 
    Absence::create([
        'user_id' => $user->id,
        'eleve_id' => $eleves[$i]->id,
        'matiere' => $request->input('selectedOption'),
        'is_absent' => $is_absent,
        'date' => $request->input('selectedDateTime'),
    ]);
    if(!$is_absent){
        $notification = new Notification();
        $notification->user_id = $user->id; 
        $notification->body = $eleves[$i]->name ."   ".'absent'."   " .$request->input('selectedOption')."   ".$request->input('selectedDateTime');
        $notification->save(); 
        $destUsers=$eleves[$i]->parents;
        
        foreach($destUsers as $destUser){
            DB::table('notification_user')->insert([
                'notification_id' => $notification->id,
                'user_id' => $destUser->id
            ]); 
        } 
    }
       
    }
    return response()->json(['message' => 'Absence records inserted successfully'], 200);
});
Route::get('/getAbsences/{id}', function($id) {
    $absences = DB::table('absences')
                ->join('users', 'absences.user_id', '=', 'users.id')
                ->where('absences.eleve_id', $id)
                ->where('absences.is_absent', 0)
                ->select('absences.id', 'users.name as user_name', 'absences.matiere', 'absences.is_absent', 'absences.date')
                ->get();
    
    return response()->json(['list' => $absences], 200);
});

Route::get('/getAbsence',function(){
    $absences = DB::table('absences')
                ->join('users', 'absences.user_id', '=', 'users.id')
                ->join('eleves','absences.eleve_id','=','eleves.id')
                ->where('absences.is_absent', 0)
                ->orderBy('absences.date', 'DESC')
                ->select('eleves.name as eleve_name', 'users.name as user_name', 'absences.matiere', 'absences.is_absent', 'absences.date')
                ->get();
                return response()->json(['list' => $absences], 200);
});
Route::get('/getElevs/{id}',function($id){
    $list = Eleves::with('class', 'parents')->where('class_id',$id)->get();
   return response()->json(['list' => $list], 200);
});
Route::get('/getEmlpois/{id}',function($id){
  $list=Eleves::find($id)->class;
  return response()->json(['list'=> $list],200);
});
Route::get('/getName/{email}',function($email){
    $user=User::where('email',$email)->first();
    return response()->json(['name'=>$user->name],200); 
});
     