<?php

namespace App\Helpers;

class DepartmentHelper
{
    public static function isApprenticeship(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        $username = strtolower($user['username'] ?? '');
        $roleFr = strtolower($user['role_fr'] ?? '');
        $modeId = (int)($user['IDMode_formation'] ?? 0);

        return $modeId === 10 
            || $roleFr === 'apprentissage'
            || strpos($roleFr, 'apprentissage') !== false
            || in_array($username, ['sdtpa', 'sdtpas', 'sdtpap', 'sa'])
            || strpos($username, 'sdtpa') === 0;
    }

    public static function isPresentielOnly(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        $username = strtolower($user['username'] ?? '');
        return in_array($username, ['sdtpp', 'sdtpps', 'sdtppp', 'sdtpc', 'sdtpcs', 'sdtpcp', 'ssfep', 'sfaci', 'pedago#dfep', 'pedagoe'])
            || strpos($username, 'sdtpp') === 0
            || strpos($username, 'sdtpc') === 0;
    }

    public static function getDepartmentType(?array $user): string
    {
        if (!$user) {
            return 'general';
        }
        $username = strtolower($user['username'] ?? '');
        if (self::isApprenticeship($user)) {
            return 'apprentissage';
        }
        if (in_array($username, ['biao', 'biaos', 'biaop']) || strpos($username, 'biao') === 0) {
            return 'orientation';
        }
        if (in_array($username, ['dplm', 'dplms', 'dplmp']) || strpos($username, 'dplm') === 0) {
            return 'diplomes';
        }
        if (self::isPresentielOnly($user)) {
            return 'pedagogie';
        }
        if (in_array($username, [
            'admfine', 'admfines', 'admfinep', 'samf', 'samfs', 'sdafm', 'sdsafms', 'sdarh', 'sdarhs',
            'samrh', 'ssip', 'admfin#dfep'
        ]) 
        || strpos($username, 'admfin') === 0 
        || strpos($username, 'samf') === 0
        || strpos($username, 'sdafm') === 0
        || strpos($username, 'sdarh') === 0
        || strpos($username, 'samrh') === 0
        || strpos($username, 'ssip') === 0) {
            return 'administration';
        }
        return 'general';
    }
}
