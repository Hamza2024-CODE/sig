<?php

namespace App\Database;

trait HasScopedQuery
{
    /**
     * Applies establishment and wilaya scopes automatically to Eloquent queries.
     * Usage: Apprenant::scoped()->find($id)
     */
    public function scopeScoped($query)
    {
        return ScopedQuery::for(static::class);
    }
}
