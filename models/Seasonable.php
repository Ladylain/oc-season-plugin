<?php namespace Ladylain\Season\Models;

use Model;

class Seasonable extends Model
{
    use \October\Rain\Database\Traits\Validation;

    public $table = 'ladylain_season_modelable_season';

    public $timestamps = false;
    public $rules = [];

    protected $fillable = [
        'modelable_id',
        'modelable_type',
        'season_id',
    ];

    public $morphTo = [
        'modelable' => []
    ];

    public $belongsTo = [
        'season' => [
            'Ladylain\Season\Models\SeasonDefinition',
            'table' => 'ladylain_season_seasons',
            'key' => 'season_id'
        ]
    ];

}

    