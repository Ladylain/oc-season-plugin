<?php namespace Ladylain\Season\Facades;

use October\Rain\Support\Facade;

/**
 * Season facade
 *
 * @method static mixed getSeasonFromRequest()
 * @method static mixed getSeasonFromId()
 * @method static mixed getSeasonFromSession()
 * @method static mixed listEnabled()
 * @method static mixed getPrimarySeason()
 * @method static mixed listSeasons()
 * @method static string getPatternFromPage()
 * @method static string getUrlFromPattern()
 * @method static string withPreservedQueryString()
 * @method static void resetCache()
 * 
 * @see \Ladylain\Season\Classes\SeasonManager
 */
class Season extends Facade
{
    /**
     * getFacadeAccessor gets the registered name of the component.
     */
    protected static function getFacadeAccessor()
    {
        return 'ladylain.seasons';
    }
}
