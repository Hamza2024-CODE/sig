<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dfep extends Model
{
    protected $table      = 'dfep';
    protected $primaryKey = 'IDDFEP';
    public    $timestamps = false;

    protected $fillable = [
        'IDWilayaa',
        'Nom',
        'NomFr',
        'Code',
        'Adresse',
        'Tel',
        'Email',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function wilaya()
    {
        return $this->belongsTo(Wilaya::class, 'IDWilayaa', 'IDWilayaa');
    }

    public function etablissements()
    {
        return $this->hasMany(Etablissement::class, 'IDDFEP', 'IDDFEP');
    }
}
