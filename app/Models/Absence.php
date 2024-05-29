<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'eleve_id',
        'matiere',
        'is_absent',
        'date',
    ];
}
