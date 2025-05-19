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

        $this->hasOneThrough['season'] = [
            SeasonDefinition::class,
            'key' => 'id',
            'through' => Seasonable::class,
            'throughKey' => 'season_id',
            'otherKey' => 'model_id',
            'secondOtherKey' => 'id'

        ];

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
        return Season::orderBy('name')
                     ->pluck('name','id')
                     ->toArray();
    }
}