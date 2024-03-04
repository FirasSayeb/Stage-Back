<?php

namespace App\Models;
use App\Models\Haves;
use App\Models\User; 
use App\Models\Classes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Eleves extends Model
{
    use HasFactory; 
    protected $fillable = ['name'];
    public function class() 
    {
        return $this->belongsTo(Classes::class, 'class_id'); 
    }

    public function parents() 
    {
        return $this->belongsToMany(User::class, 'haves', 'eleve_id', 'user_id');
    }

}
