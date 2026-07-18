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
            || in_array($username, ['sdtpa', 'sdtpas', 'sa']);
    }

    public static function isPresentielOnly(?array $user): bool
    {
        if (!$user) {
            return false;
        }
        $username = strtolower($user['username'] ?? '');
        return in_array($username, ['sdtpp', 'sdtpps', 'sdtpc', 'sdtpcs', 'ssfep', 'sfaci', 'pedago#dfep', 'pedagoe']);
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
        if (in_array($username, ['biao', 'biaos'])) {
            return 'orientation';
        }
        if (in_array($username, ['dplm', 'dplms'])) {
            return 'diplomes';
        }
        if (self::isPresentielOnly($user)) {
            return 'pedagogie';
        }
        if (in_array($username, [
            'admfine', 'admfines', 'samf', 'samfs', 'sdafm', 'sdsafms', 'sdarh', 'sdarhs',
            'samrh', 'ssip', 'admfin#dfep'
        ])) {
            return 'administration';
        }
        return 'general';
    }
}
