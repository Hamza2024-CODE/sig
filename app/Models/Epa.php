<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Epa extends Model
{
    protected $table      = 'epas';
    protected $primaryKey = 'IDepas';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'id',
        'code',
        'libelleArabe',
        'commune_id',
        'wilaya_id',
        'libelleLatin',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function commune()
    {
        return $this->belongsTo(\Commune::class, 'commune_id', 'commune_id');
    }

    public function wilaya()
    {
        return $this->belongsTo(\Wilaya::class, 'wilaya_id', 'wilaya_id');
    }
}