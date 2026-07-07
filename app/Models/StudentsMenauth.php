<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentsMenauth extends Model
{
    protected $table      = 'students_menauth';
    protected $primaryKey = 'id';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'matricule',
        'lastname_ar',
        'firstname_ar',
        'lastname_latin',
        'firstname_latin',
        'birth_date',
        'birth_place',
        'etab',
        'division',
        'dateexit',
        'yearexit',
        'nin',
    ];
}