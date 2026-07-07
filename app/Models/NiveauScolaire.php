<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NiveauScolaire extends Model
{
    protected $table      = 'niveau_scolaires';
    protected $primaryKey = 'IDniveau_scolaires';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'id',
        'code',
        'libelleArabe',
        'libelleLatin',
        'cycle',
        'annee',
        'i_p',
        'isActived',
    ];
}