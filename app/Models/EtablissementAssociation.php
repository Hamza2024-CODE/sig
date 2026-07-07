<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementAssociation extends Model
{
    protected $table      = 'etablissement_association';
    protected $primaryKey = 'IDEtablissement_Association';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDetablissement',
        'IDAssociation_Clube',
        'NumDecision',
        'DateDecision',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }

    public function associationClube()
    {
        return $this->belongsTo(\AssociationClube::class, 'IDAssociation_Clube', 'IDAssociation_Clube');
    }
}