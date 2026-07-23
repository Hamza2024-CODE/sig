<?php

namespace App\Database;

use Illuminate\Database\MySqlConnection;

class CustomMySqlConnection extends MySqlConnection
{
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $query = QueryFilterHelper::filterEtablissements($query);
        return parent::select($query, $bindings, $useReadPdo);
    }

    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        $query = QueryFilterHelper::filterEtablissements($query);
        return parent::cursor($query, $bindings, $useReadPdo);
    }

    public function statement($query, $bindings = [])
    {
        $query = QueryFilterHelper::filterEtablissements($query);
        return parent::statement($query, $bindings);
    }

    public function affectingStatement($query, $bindings = [])
    {
        $query = QueryFilterHelper::filterEtablissements($query);
        return parent::affectingStatement($query, $bindings);
    }
}
