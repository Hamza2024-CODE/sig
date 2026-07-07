<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeurBranche extends Model
{
    protected $table      = 'employeur_branche';
    protected $primaryKey = 'IDEmployeur_Branche';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEmployeur',
        'IDBranche',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function employeur()
    {
        return $this->belongsTo(\Employeur::class, 'IDEmployeur', 'IDEmployeur');
    }

    public function branche()
    {
        return $this->belongsTo(\Branche::class, 'IDBranche', 'IDBranche');
    }
}