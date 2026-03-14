<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class BranchScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Avoid infinite recursion if the model being queried is User
        if ($model instanceof \App\Models\User) {
            return;
        }

        if (Auth::check() && !Auth::user()->hasRole('admin')) {
            $branchId = Auth::user()->branch_id;
            
            if ($branchId) {
                // Determine the table name to avoid ambiguous column errors in joins
                $table = $model->getTable();
                
                // If the model itself is Branch, we filter by its ID, otherwise by its branch_id
                if ($table === 'branches') {
                    $builder->where("{$table}.id", $branchId);
                } else {
                    $builder->where("{$table}.branch_id", $branchId);
                }
            }
        }
    }
}
