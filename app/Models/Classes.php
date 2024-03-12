<?php

namespace App\Models;

use App\Models\Eleves;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Classes extends Model
{
    use HasFactory;
    public function eleves()
{
    return $this->hasMany(Eleves::class, 'class_id');
}

}
