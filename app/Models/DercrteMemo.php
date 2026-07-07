<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DercrteMemo extends Model
{
    protected $table      = 'dercrte_memo';
    protected $primaryKey = 'IDDercrte_memo';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDDecretMfep',
        'Pdf',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function decretMfep()
    {
        return $this->belongsTo(\DecretMfep::class, 'IDDecretMfep', 'IDDecretMfep');
    }
}