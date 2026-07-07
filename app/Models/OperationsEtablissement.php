<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperationsEtablissement extends Model
{
    protected $table      = 'operations_etablissement';
    protected $primaryKey = 'IDOperations_Etablissement';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDetablissement',
        'IDOperations',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function operation()
    {
        return $this->belongsTo(\Operation::class, 'IDOperations', 'IDOperations');
    }
}