<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accesuserdetail extends Model
{
    protected $table      = 'accesuserdetail';
    protected $primaryKey = 'IDaccesuserdetail';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Datepc',
        'IDaccesuser',
        'operation',
        'Dossier',
        'Dateserv',
        'NomUser',
        'nomfichier',
        'idfichier',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function accesuser()
    {
        return $this->belongsTo(\Accesuser::class, 'IDaccesuser', 'IDaccesuser');
    }
}