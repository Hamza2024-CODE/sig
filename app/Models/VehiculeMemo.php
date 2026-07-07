<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehiculeMemo extends Model
{
    protected $table      = 'vehicule_memo';
    protected $primaryKey = 'IDVehicule_memo';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'photo',
        'IDVehicule',
        'Num',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function vehicule()
    {
        return $this->belongsTo(\Vehicule::class, 'IDVehicule', 'IDVehicule');
    }
}