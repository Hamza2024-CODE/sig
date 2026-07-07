<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dpic extends Model
{
    protected $table      = 'dpic';
    protected $primaryKey = 'IDDpic';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [];
}