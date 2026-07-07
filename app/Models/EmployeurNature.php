<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeurNature extends Model
{
    protected $table      = 'employeur_nature';
    protected $primaryKey = 'IDEmployeur_Nature';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'NumOrd',
        'AbrAr',
        'AbrFr',
        'Description',
        'IDEmployeurType',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function employeurType()
    {
        return $this->belongsTo(\EmployeurType::class, 'IDEmployeurType', 'IDEmployeurType');
    }
}