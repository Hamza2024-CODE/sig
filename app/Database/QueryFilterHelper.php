<?php

namespace App\Database;

class QueryFilterHelper
{
    public static function filterEtablissements(string $sql): string
    {
        // Don't modify if it's not a SELECT query
        if (!preg_match('/^\s*select/i', $sql)) {
            return $sql;
        }

        // Don't modify if it already checks activee status
        if (preg_match('/activee\s*=\s*/i', $sql) || preg_match('/activee\s+is\s+/i', $sql) || preg_match('/activee\s*<>\s*/i', $sql)) {
            return $sql;
        }

        // Find etablissement and its alias
        $pattern = '/\b`?etablissement`?\b(?:\s+(?:as\s+)?`?([a-zA-Z0-9_]+)`?)?/i';
        if (!preg_match_all($pattern, $sql, $matches, PREG_SET_ORDER)) {
            return $sql;
        }

        // Exclude common SQL keywords from being matched as alias
        $keywords = ['order', 'group', 'limit', 'where', 'join', 'left', 'right', 'inner', 'on', 'using', 'union', 'select', 'from', 'as'];
        $alias = 'etablissement';
        foreach ($matches as $match) {
            if (!empty($match[1])) {
                $candidate = strtolower($match[1]);
                if (!in_array($candidate, $keywords)) {
                    $alias = $match[1];
                    break;
                }
            }
        }

        $filter = "`{$alias}`.activee = 0";

        // Check if there is already a WHERE clause in the query
        if (preg_match('/\bWHERE\b/i', $sql)) {
            // Inject right after the first WHERE keyword
            $sql = preg_replace('/\b(WHERE)\b/i', '$1 ' . $filter . ' AND', $sql, 1);
        } else {
            // No WHERE clause. Inject before ORDER BY, GROUP BY, LIMIT, UNION, or at the end.
            $injectKeywords = ['/ORDER\s+BY/i', '/GROUP\s+BY/i', '/LIMIT/i', '/UNION/i'];
            $injected = false;
            foreach ($injectKeywords as $kwPattern) {
                if (preg_match($kwPattern, $sql)) {
                    $sql = preg_replace($kwPattern, 'WHERE ' . $filter . ' $0', $sql, 1);
                    $injected = true;
                    break;
                }
            }
            if (!$injected) {
                $sql .= ' WHERE ' . $filter;
            }
        }

        return $sql;
    }
}
