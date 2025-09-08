<?php namespace Ladylain\Season\Classes;

use App;
use Event;
use Site;
use Manifest;
use Exception;
use Cms\Classes\Page;
use Ladylain\Season\Models\SeasonDefinition;
use Ladylain\Season\Classes\SeasonCollection;
use Ladylain\Season\Models\Seasonable;
use Session;
use October\Rain\Router\Router as RainRouter;
use System\Classes\PluginManager;

/**
 * SeasonManager class manages seasons
 * 
 * @package ladylain\season
 * @author Lucas Palomba
 */
class SeasonManager 
{

    use SeasonManager\HasActiveSeason;

    /**
     * @var string keys for manifest storage
     */
    const MANIFEST_SEASONS = 'seasons.all';

    /**
     * @var array seasons collection of seasons
     */
    protected $seasons;

    /**
     * @var array seasonIdCache caches seasons by their identifier
     */
    protected $seasonIdCache = [];
    /**
     * @var PluginManager pluginManager
     */
    protected $pluginManager;

    /**
     * __construct this class
     */
    public function __construct()
    {
        $this->pluginManager = PluginManager::instance();
    }
    /**
     * instance creates a new instance of this singleton
     */
    public static function instance(): static
    {
        return App::make('ladylain.seasons');
    }
/**
     * getSeasonFromRequest locates the season based on the hostname and URI
     */
    public function getSeasonFromRequest(string $rootUrl, string $uri)
    {
        // @deprecated passing a hostname will be removed in v4
        if (!str_contains($rootUrl, '://')) {
            $rootUrl = "https://{$rootUrl}";
        }
        
        $seasons = $this->listEnabled();
        $host = parse_url($rootUrl, PHP_URL_HOST);

        // Begin fallback matching
        $rootSeasons = $seasons;

        // Matching either the hostname or app URL
        $seasons = $seasons->filter(function($season) use ($host, $uri) {
            return $season->matchesBaseUri($uri);
        });

        if($seasons->count() === 0 && $cookieSeason = $this->getSeasonFromSession()){
            $season = $this->getSeasonFromId($cookieSeason->id);
            $season->isFallbackMatch = true;

            return $season;
        }
        
        // Found a root host match without any valid prefix
        if ($rootSeasons->count() > 0 && $seasons->count() === 0 && !$cookieSeason ){
            $seasons = $rootSeasons->each(function($season) {
                $season->isFallbackMatch = true;
            });
        }

        return $seasons->first();
    }
    /**
     * getSeasonFromId
     */
    public function getSeasonFromId($id)
    {
        if (isset($this->seasonIdCache[$id])) {
            return $this->seasonIdCache[$id];
        }

        return $this->seasonIdCache[$id] = $this->listSeasons()->find($id);
    }

    /**
     * getSeasonFromSession
     */
    public function getSeasonFromSession()
    {
        $seasonId = Session::get('season_id');
        if ($seasonId) {
            return $this->getSeasonFromId($seasonId);
        }
        return null;
    }

    public function listEnabled()
    {
        return $this->listSeasons()->isEnabled();
    }

    public function seasonUrl(Page $page, SeasonDefinition $saison, array $parameters = [])
    {
        $pattern = $this->getPatternFromPage($page, $saison);

        $urlPattern= $this->getUrlFromPattern($pattern, $page, $saison, $parameters);
        $url = $this->withPreservedQueryString(
            $urlPattern,
            $page,
            $saison
        );
        return $url;
    }

    /**
     * getPrimarySeason
     */
    public function getPrimarySeason()
    {
        return $this->listSeasons()->isPrimary()->first();
    }

    /**
     * listSeasons
     */
    public function listSeasons()
    {

        if ($this->seasons !== null) {
            return $this->seasons;
        }
        
        if (Manifest::has(self::MANIFEST_SEASONS)) {
            $this->seasons = $this->listSeasonsFromManifest(
                (array) Manifest::get(self::MANIFEST_SEASONS)
            );
        }
        else {
            try {
                $this->seasons = SeasonDefinition::get();
            }
            catch (Exception $ex) {
                return new SeasonCollection([SeasonDefinition::makeFallbackInstance()]);
            }
            
            Manifest::put(
                self::MANIFEST_SEASONS,
                $this->listSeasonsForManifest($this->seasons)
            );
        }
        
        return $this->seasons;
    }


    /**
     * getPatternFromPage
     */
    protected  function getPatternFromPage(Page $page, SeasonDefinition $saison): string
    {
        $pattern = $page->url;

        return $pattern;
    }

        /**
     * getUrlFromPattern
     */
    protected  function getUrlFromPattern(string $urlPattern, Page $page, SeasonDefinition $saison, array $parameters)
    {
        // if($saison && $saison->code !== null){
        //     $parameters['saison'] = $saison->code;
        // }

        $router = new RainRouter;
        
        $path = $router->urlFromPattern($urlPattern, $parameters);
        $site = Site::getActiveSite();
        return rtrim($site->base_url .'/'.$saison->getAttributeTranslated('code', $site->locale) . $path, '/');
    }

    /**
     * withPreservedQueryString makes sure to add any existing query string to the redirect url.
     */
    protected  function withPreservedQueryString(string $url, Page $page, SeasonDefinition $site): string
    {
        $query = get();
               
        $queryStr = http_build_query($query);
        
        return $queryStr ? $url . '?' . $queryStr : $url;
    }

    /**
     * listSeasonsFromManifest
     */
    protected function listSeasonsFromManifest($seasons)
    {
        $items = [];

        foreach ($seasons as $attributes) {
            $season = new SeasonDefinition;
            $season->attributes = $attributes;
            $season->syncOriginal();
            $items[] = $season;
        }

        return new SeasonCollection($items);
    }

        /**
     * listSeasonsForManifest
     */
    protected function listSeasonsForManifest($seasons)
    {
        $items = [];

        foreach ($seasons as $season) {
            $store = $season->attributes;
            $items[] = $store;
        }

        return $items;
    }

    /**
     * broadcastSeasonChange is a generic event used when the season changes
     */
    protected function broadcastSeasonChange($seasonId)
    {
        /**
         * @event season.changed
         * Fires when the season has been changed.
         *
         * Example usage:
         *
         *     Event::listen('season.changed', function($id) {
         *         \Log::info("Season has been changed to $id");
         *     });
         *
         */
        Event::fire('season.changed', [$seasonId]);
    }

    /**
     * resetCache resets any memory or cache involved with the sites
     */
    public function resetCache()
    {
        $this->seasons = null;
        $this->seasonIdCache = [];
        Manifest::forget(self::MANIFEST_SEASONS);
    }


    static public function listAllSeasonableModelsID()
    {
        $models = [];
        $seasonableModels = Seasonable::all();
        foreach ($seasonableModels as $seasonableModel) {
            $model = $seasonableModel->modelable;
            if ($model) {
                $models[] = $model->id;
            }
        }
        return $models; // Return unique models by class name
        // on 
    }
}
