<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operationetat extends Model
{
    protected $table      = 'operationetat';
    protected $primaryKey = 'IDOperationEtat';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}