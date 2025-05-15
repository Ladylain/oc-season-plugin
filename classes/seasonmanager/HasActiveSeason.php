<?php namespace Ladylain\Season\Classes\SeasonManager;

use App;
use Cms;
use Event;
use Config;
use Ladylain\Season\Models\SeasonDefinition;
use Session;
use Site;

/**
 * HasActiveSeason
 *
 * @package ladylain\season
 * @author Lucas Palomba
 */
trait HasActiveSeason
{

    /**
     * getActiveSeason
     */
    public function getActiveSeason()
    {
        return $this->getSeasonFromId($this->getActiveSeasonId())
            ?: ($this->getSeasonFromSession() ?: $this->getPrimarySeason());
    }

    /**
     * getActivegetActiveSeasonIdSiteId
     */
    public function getActiveSeasonId()
    {
        return Config::get('ladylain.season::ladylain.active_season');
    }

    /**
     * setActiveSeasonId
     */
    public function setActiveSeasonId($id)
    {
        Config::set('ladylain.season::ladylain.active_season', $id);
        
        // Set the session for 1 day
        Session::put('season_id', $id);

        /**
         * @event ladylain.season.setActiveSeason
         * Fires when the active season has been changed.
         *
         * Example usage:
         *
         *     Event::listen('ladylain.season.setActiveSeason', function($id) {
         *         \Log::info("Season has been changed to $id");
         *     });
         *
         */
        Event::fire('ladylain.season.setActiveSeason', [$id]);

        $this->broadcastSeasonChange($id);
    }
    


    /**
     * setActiveSeason
     */
    public function setActiveSeason($season)
    {
        $this->setActiveSeasonId($season->id);
    }

    /**
     * applyActiveSeasonId
     */
    public function applyActiveSeasonId($id)
    {
        if ($season = $this->getSeasonFromId($id)) {
            $this->applyActiveSeason($season);
        }
    }

    /**
     * applyActiveSeason applies active season configuration values to the application,
     * typically used for frontend requests.
     */
    public function applyActiveSeason(SeasonDefinition $season)
    {   
        if($site_prefix = Site::getActiveSite()->route_prefix){
            $prefix = $site_prefix . '/' . $season->code;
        }else {
            $prefix = '/'. $season->code;
        }

        Cms::setUrlPrefix($prefix);
        $this->setActiveSeason($season);
    }
}

    