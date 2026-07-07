<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassificationRnfc extends Model
{
    protected $table      = 'classification_rnfc';
    protected $primaryKey = 'IDclassification_rnfc';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}