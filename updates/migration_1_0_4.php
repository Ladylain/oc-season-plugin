<?php namespace Ladylain\Season\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ladylain_season_modelable_season', function($table) {
            $table->string('modelable_type')->nullable()->change();
            $table->string('modelable_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('ladylain_season_modelable_season', function($table) {
            $table->string('modelable_type')->nullable(false)->change();
            $table->integer('modelable_id')->nullable(false)->change();
        });
    }
};