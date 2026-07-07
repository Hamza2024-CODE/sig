<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sitfamille extends Model
{
    protected $table      = 'sitfamille';
    protected $primaryKey = 'IDSitfamille';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
    ];
}