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

        // Find from/join etablissement and wrap it in a subquery
        $pattern = '/\b(FROM|JOIN)\s+`?etablissement`?(?:\s+(?:as\s+)?`?([a-zA-Z0-9_]+)`?)?/i';
        
        $keywords = ['order', 'group', 'limit', 'where', 'join', 'left', 'right', 'inner', 'on', 'using', 'union', 'select', 'from', 'as'];

        return preg_replace_callback($pattern, function($matches) use ($keywords) {
            $clause = $matches[1]; // FROM or JOIN
            $alias = !empty($matches[2]) ? $matches[2] : '';
            
            if (!empty($alias) && in_array(strtolower($alias), $keywords)) {
                // The matched "alias" is actually a SQL keyword (e.g. FROM etablissement ORDER BY ...)
                return $clause . " (SELECT * FROM etablissement WHERE activee = 0) etablissement " . $alias;
            }
            
            if (!empty($alias)) {
                return $clause . " (SELECT * FROM etablissement WHERE activee = 0) " . $alias;
            } else {
                return $clause . " (SELECT * FROM etablissement WHERE activee = 0) etablissement";
            }
        }, $sql);
    }
}
