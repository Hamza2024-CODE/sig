<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Minister extends Model
{
    protected $table      = 'minister';
    protected $primaryKey = 'IDMinister';
    public    $timestamps = false; // Legacy tables usually don't have Laravel default timestamps

    protected $fillable = [
        'Nom',
        'NomFr',
        'Adrs',
        'AdresFr',
        'Tel',
        'Fax',
        'Fb',
        'url',
        'Email',
        'Tel2',
        'Email2',
        'ip_Publique',
        'NomUser',
        'Motdepass',
        'CodeConf',
        'Fax2',
        'ipsrvhfsql',
        'portsrvhfsql',
        'ipsrvmysql',
        'portsrvmusql',
        'ipsrvftp',
        'portsrvftp',
        'ipsrvhttp',
        'portsrvhttp',
        'ipsrvhttps',
        'portsrvhttps',
        'versionmiseajour',
        'versionactuel',
        'dnssrvhttp',
        'dnssrvhttps',
        'ipsrvh1fsql',
        'portsrv1hfsql',
    ];
}