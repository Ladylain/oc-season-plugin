<?php namespace Acme\Users\Updates;

use Seeder;
use Ladylain\Season\Models\SeasonDefinition;

class SeedUsersTable extends Seeder
{
    public function run()
    {
        $season = SeasonDefinition::create([
            'code' => 'hiver',
            'name' => 'Hiver',
            'active' => true,
            'icon' => 'ph-snowflake',
        ]);

        $season = SeasonDefinition::create([
            'code' => 'ete',
            'name' => 'Été',
            'active' => true,
            'icon' => 'ph-sun',
        ]);
    }
}