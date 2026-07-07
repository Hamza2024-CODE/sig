<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProcedureDisciplinaire extends Model
{
    protected $table      = 'procedure_disciplinaire';
    protected $primaryKey = 'IDProcedure_Disciplinaire';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
    ];
}