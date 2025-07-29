<?php namespace Ladylain\Season\Behaviors;

use App;
use Db;
use Ladylain\Season\Models\Seasonable;
use Ladylain\Season\Models\SeasonDefinition;
use October\Rain\Extension\ExtensionBase;
use Ladylain\Season\Scopes\SeasonScope;
/**
 * Seasonable model extension
 *
 * Usage:
 *
 * In the model class definition:
 *
 *   public $implement = ['@Ladylain.Season.Behaviors.SeasonableModel'];
 *
 *
 */
class SeasonableModel extends ExtensionBase
{

    /**
     * @var \October\Rain\Database\Model The model instance this behavior is attached to.
     */
    protected $model;

    /**
     * __construct
     */
    public function __construct($model)
    {
        $this->model = $model;

        $model->morphOne['seasonable'] = [
            Seasonable::class,
            'name'  => 'modelable',
            'table' => 'ladylain_season_modelable_season',
            'replicate' => false
        ];

        $model->hasOneThrough['season'] = [
            \Ladylain\Season\Models\SeasonDefinition::class,
            'through' => Seasonable::class,
            'throughKey' => 'id',
            'key' => 'season_id',
            'modelKey' => 'seasonable_id',
            'modelType' => get_class($model),
            'replicate' => false
            
        ];

        // save the seasonable relation before saving the model
        $model->bindEvent('model.beforeSave', function () use ($model) {
            if ($model->seasonable_id) {
                $seasonable = $model->seasonable ?? new Seasonable();
                $seasonable->season_id = $model->seasonable_id;
                $seasonable->modelable_type = get_class($model);
                $seasonable->save();
                // Utilisation du Deferred Binding pour différer la liaison
                $model->seasonable()->add($seasonable, post('_session_key', null, true));
            }elseif($model->seasonable_id == ''){
                // If seasonable_id is empty, we delete the existing relation
                $model->seasonable()->delete();
            }
        
            unset($model->seasonable_id);
        });

        // // Clean up indexes when this model is deleted
        // $model->bindEvent('model.afterDelete', function() use ($model) {
        //     Db::table('ladylain_season_modelable_season')
        //         ->where('model_id', $model->getKey())
        //         ->where('model_type', get_class($model))
        //         ->delete();
        // });
        if( !App::runningInBackend()){

            $model::addGlobalScope(new SeasonScope);
        }

    }

    

    public function getSeasonAttribute()
    {
        // Load the pivot + its definition in a single call
        $pivot = $this->model->seasonable()->with('season')->first();
        return $pivot ? $pivot->season : null;
    }

    public function getSeasonNameAttribute()
    {
        return $this->model->seasonable ? $this->model->seasonable->season->name : null;
    }

    public function getSeasonIdAttribute()
    {
        return $this->model->seasonable ? $this->model->seasonable->season_id : null;
    }

        /**
     * Pour alimenter le dropdown
     */
    public function getSeasonsOptions()
    {
        return [
            '' => 'aucune saison',
         ] + SeasonDefinition::orderBy('name')
                     ->pluck('name','id')
                     ->toArray();
    }
}