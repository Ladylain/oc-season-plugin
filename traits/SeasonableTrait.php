<?php namespace Ladylain\Season\Traits;

use Ladylain\Season\Models\SeasonDefinition;

trait SeasonableTrait
{
    public function initializeSeasonableTrait()
    {
        // on déclare la relation polymorphe many-to-many
        $this->morphToMany['seasons'] = [
            SeasonDefinition::class,
            'name'  => 'modelable',
            'table' => 'ladylain_season_modelable_season',
        ];
    }

    /**
     * On renvoie **l’ID** de la 1ère Saison ou null
     * pour que le dropdown sache quoi sélectionner.
     */
    public function getSeasonableAttribute()
    {
        $s = $this->seasons()->first();
        return $s ? $s->id : null;
    }

    /**
     * On renvoie la 1ère Saison ou null
     */
    public function getSeasonAttribute()
    {
        return $this->seasons()->first();
    }


    /**
     * Pour affecter/disconnecter directement via $model->season = $id;
     */
    public function setSeasonableAttribute($value)
    {
        $this->seasons()->sync($value ? [$value] : []);
    }

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