<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EtsformBranche extends Model
{
    protected $table      = 'etsform_branche';
    protected $primaryKey = 'IDEtsForm_Branche';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'IDEts_Form',
        'IDBranche',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function etsForm()
    {
        return $this->belongsTo(\EtsForm::class, 'IDEts_Form', 'IDEts_Form');
    }

    public function branche()
    {
        return $this->belongsTo(\Branche::class, 'IDBranche', 'IDBranche');
    }
}