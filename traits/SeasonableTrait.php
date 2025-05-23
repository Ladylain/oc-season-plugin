<?php namespace Ladylain\Season\Traits;

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

        // $this->hasOneThrough['season'] = [
        //     SeasonDefinition::class,
        //     'key' => 'id',
        //     'through' => Seasonable::class,
        //     'throughKey' => 'season_id',
        //     'otherKey' => 'model_id',
        //     'secondOtherKey' => 'id'

        // ];

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

    public function beforeSave()
    {
        if ($this->seasonable_id) {
            $seasonable = $this->seasonable ?? new Seasonable();
            $seasonable->season_id = $this->seasonable_id;
            $seasonable->modelable_type = self::class;
            $seasonable->modelable_id = $this->id;
            $seasonable->save();
        }
        unset($this->seasonable_id);
    }
    // /**
    //  * On renvoie **l’ID** de la 1ère Saison ou null
    //  * pour que le dropdown sache quoi sélectionner.
    //  */
    // public function getSeasonableAttribute()
    // {
    //     $s = $this->seasons()->first();
    //     return $s ? $s->id : null;
    // }

    // /**
    //  * On renvoie la 1ère Saison ou null
    //  */
    // public function getSeasonAttribute()
    // {
    //     return $this->seasons()->first();
    // }


    // /**
    //  * Pour affecter/disconnecter directement via $model->season = $id;
    //  */
    // public function setSeasonableAttribute($value)
    // {
    //     $this->seasons()->sync($value ? [$value] : []);
    // }

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