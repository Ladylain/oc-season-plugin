<?php namespace Ladylain\Season\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

/**
 * Migration class adds is_primary field to seasons table
 *
 * @link https://docs.octobercms.com/3.x/extend/database/structure.html
 */
return new class extends Migration
{
    /**
     * up builds the migration
     */
    public function up()
    {
        Schema::table('ladylain_season_seasons', function(Blueprint $table) {
            $table->boolean('is_primary')->default(false);
        });
    }

    /**
     * down reverses the migration
     */
    public function down()
    {
        Schema::table('ladylain_season_seasons', function(Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
