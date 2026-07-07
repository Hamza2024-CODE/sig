<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Convocation extends Model
{
    protected $table      = 'convocation';
    protected $primaryKey = 'IDconvocation';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Num',
        'Date',
        'IDapprenant',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function apprenant()
    {
        return $this->belongsTo(\Apprenant::class, 'IDapprenant', 'IDapprenant');
    }
}