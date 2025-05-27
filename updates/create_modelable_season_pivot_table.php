<?php namespace Ladylain\Season\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;
use October\Rain\Database\Schema\Blueprint;

/**
 * CreateVariantsPivotTable Migration
 * This migration creates a pivot table for the many-to-many relationship between seasons and variants.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ladylain_season_modelable_season', function($table) {
            $table->increments('id');
            $table->integer('season_id')->unsigned()->index();
            $table->integer('modelable_id')->unsigned()->nullable();
            $table->string('modelable_type')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ladylain_season_modelable_season');
    }

};