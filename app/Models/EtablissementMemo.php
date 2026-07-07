<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtablissementMemo extends Model
{
    protected $table      = 'etablissement_memo';
    protected $primaryKey = 'IDEtablissement_memo';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'photo',
        'IDetablissement',
        'Num',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etablissement()
    {
        return $this->belongsTo(\Etablissement::class, 'IDetablissement', 'IDetablissement');
    }
}