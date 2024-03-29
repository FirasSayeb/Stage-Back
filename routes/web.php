<?php
use App\Models\User;
use App\Models\Notes;
use App\Models\Actualite;
use Laravel\Prompts\Note;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
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


Route::get('/note',function(){
    return View('note');
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
                Notes::create([
                    'eleve_id' => $eleve_id,
                    'matiere' => $matiere,
                    'note' => $note,
                ]);
            } else {
                // Handle missing or invalid data
                $success = false;
            }
        }

        if ($success) {
            return response()->json(['message' => 'File uploaded and processed successfully'], 200);
        } else {
            return response()->json(['error' => 'Some rows could not be processed.'], 400);
        }
    } else {
        return response()->json(['error' => 'No file uploaded'], 400);
    }
});
