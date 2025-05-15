<?php namespace Ladylain\Season\Components;

use Season;
use Cms\Classes\ComponentBase;
use Site;
use Cms;
use Event;
use Cms\Classes\Page;

/**
 * SeasonPicker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class SeasonPicker extends ComponentBase
{

    /**
     * @var array seasonsCache for multiple calls
     */
    protected $seasonsCache;

    /**
     * @var array allSeasonsCache for multiple calls
     */
    protected $allSeasonsCache;

    public function componentDetails()
    {
        return [
            'name' => 'Season Picker',
            'description' => 'A component to change season on frontend.'
        ];
    }

    /**
     * @link https://docs.octobercms.com/3.x/element/inspector-types.html
     */
    public function defineProperties()
    {
        return [];
    }

    /**
     * seasons  lazily loads the available seasons
     */
    public function seasons(){

        return $this->seasonsCache ??= $this->allSeasons();
    }
    
    public function isPageSeasonable(){
        return $this->getPage()->isSeasonable();
    }

    /**
     * allSeasons lazily loads the available seasons
     */
    public function allSeasons()
    {
        if ($this->allSeasonsCache !== null) {
            return $this->allSeasonsCache;
        }
        $seasons = Season::listEnabled();
        foreach ($seasons as $season) {
            $season->setUrlOverride(Season::seasonUrl(
                $this->getPage(), 
                $season, 
                $this->getRouter()->getParameters()
            ));
        }
        return $this->allSeasonsCache = $seasons;
    }
    
}
