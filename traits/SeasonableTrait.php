<?php namespace Ladylain\Season\Traits;

use App;
use Ladylain\Season\Models\Seasonable;
use Ladylain\Season\Models\SeasonDefinition;

trait SeasonableTrait
{
    public function initializeSeasonableTrait()
    {

        $this->morphOne['seasonable'] = [
            Seasonable::class,
            'name'  => 'modelable',
            'table' => 'ladylain_season_modelable_season',
        ];
        $this->bindEvent('model.beforeSave', function () {
            if ($this->seasonable_id) {
                $seasonable = $this->seasonable ?? new Seasonable();
                $seasonable->season_id = $this->seasonable_id;
                $seasonable->modelable_type = self::class;
                $seasonable->modelable_id = $this->id;
                $seasonable->save();
            }
            unset($this->seasonable_id);
        });

            
    }
        
    /**
     * Accès direct (méthode ou accessor) à la définition de la saison.
     */
    public function getSeasonAttribute()
    {
        // Charge le pivot + sa définition en un seul appel
        $pivot = $this->seasonable()->with('season')->first();
        return $pivot
        ? $pivot->definition
        : null;
    }

    public function getSeasonIdAttribute()
    {
        return $this->seasonable ? $this->seasonable->season_id : null;
    }


    /**
     * Pour alimenter le dropdown
     */
    public function getSeasonsOptions()
    {
        return SeasonDefinition::orderBy('name')
                     ->pluck('name','id')
                     ->toArray();
    }
}