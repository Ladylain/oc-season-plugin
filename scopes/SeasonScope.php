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

        if ($activeSeason) {
            if (!$model->seasonable){
                // If the model does not have a seasonable relation, we can skip the scope
                return;
            }
            $builder->whereHas('seasonable', function ($query) use ($activeSeason) {
                $query->where('season_id', $activeSeason->id);
            });
        }
    }

    /**
     * Extend the Eloquent query builder.
     */
    public function extend(Builder $builder)
    {
        // No additional extensions needed for this scope
    }
}