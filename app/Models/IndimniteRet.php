<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndimniteRet extends Model
{
    protected $table      = 'indimnite_ret';
    protected $primaryKey = 'IDIndimnite_ret';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'CODE_INDM',
        'INT_INDM',
        'INT_FR_INDM',
        'CODE_PRTIE',
        'CODE_ARTCL',
        'CODE_CHPTR',
        'TypeInd',
        'VleurPorc',
        'MantInd',
        'cConcCalcAss',
    ];
}