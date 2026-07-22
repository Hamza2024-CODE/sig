<?php

namespace App\Database;

abstract class ScopedRepository
{
    /**
     * Subclasses must define the target Eloquent Model class name.
     */
    protected string $modelClass;

    /**
     * Retrieves a scoped Eloquent query builder for the repository's model.
     * Enforces Etablissement and Wilaya limits automatically.
     */
    protected function query()
    {
        if (empty($this->modelClass)) {
            throw new \RuntimeException('Repository must define $modelClass.');
        }
        return ScopedQuery::for($this->modelClass);
    }
}
