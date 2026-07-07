<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wilaya extends Model
{
    protected $table      = 'wilaya';
    protected $primaryKey = 'IDWilayaa';
    public    $timestamps = false;

    protected $fillable = [
        'Code',
        'Nom',
        'NomFr',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function dfep()
    {
        return $this->hasOne(Dfep::class, 'IDWilayaa', 'IDWilayaa');
    }

    public function dairas()
    {
        return $this->hasMany(Daira::class, 'IDWilayaa', 'IDWilayaa');
    }

    public function etablissements()
    {
        return $this->hasManyThrough(
            Etablissement::class,
            Dfep::class,
            'IDWilayaa',
            'IDDFEP',
            'IDWilayaa',
            'IDDFEP'
        );
    }
}
