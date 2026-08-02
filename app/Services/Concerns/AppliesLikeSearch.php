<?php

namespace App\Services\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait AppliesLikeSearch
{
    /**
     * Apply a grouped OR-LIKE filter across the given columns, escaping LIKE
     * wildcards in the search term.
     *
     * @param  array<int, string>  $columns
     */
    protected function applyLikeSearch(Builder $query, string $search, array $columns): void
    {
        $escaped = addcslashes($search, '%_\\');

        $query->where(function (Builder $query) use ($escaped, $columns) {
            foreach ($columns as $index => $column) {
                $query->{$index === 0 ? 'where' : 'orWhere'}($column, 'like', "%{$escaped}%");
            }
        });
    }
}
