<?php namespace Ladylain\Season\Scopes;

use Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Season;

class SeasonScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        $activeSeason = Season::getActiveSeason();


        $builder->where(function ($query) use ($activeSeason) {
            $query->whereHas('seasonable', function ($q) use ($activeSeason) {
                $q->where('season_id', $activeSeason->id);
            })->orWhereDoesntHave('seasonable');
        });
    }

    /**
     * Extend the Eloquent query builder.
     */
    public function extend(Builder $builder)
    {
        // No additional extensions needed for this scope
    }
}