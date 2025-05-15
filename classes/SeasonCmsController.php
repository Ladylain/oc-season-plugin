<?php namespace Ladylain\Season\Classes;

use Ladylain\Season\Models\SeasonDefinition;
use Cms\Classes\CmsController;
use App;
use Cms;
use Season;
use Site;
use Config;
use Request;
use Redirect;
use Cms\Classes\Controller;
use Illuminate\Routing\Controller as ControllerBase;
use Closure;
use Cms\Classes\Router;
use Cms\Classes\Theme;

class SeasonCmsController extends CmsController
{

        /**
     * __construct a new CmsController instance.
     */
    public function __construct()
    {
        parent::__construct();
        
    }

    public function run($url = '/'){
        // Check configuration for bypass exceptions
        if (Cms::urlHasException((string) $url, 'season')) {
            return App::make(Controller::class)->run($url);
        }
        // Locate site
        $site = $this->findSite(Request::root(), $url);
        
        // Locate season
        $season = $this->findSeason(Request::root(), $url, $site);
        // Remove prefixes, if applicable

        $uri = ltrim($this->parseUris($season, $site, $url),"/");
        // Enforce prefix, if applicable
        //TODO: FACTORIZE THIS
        if ($redirect = $this->redirectWithoutSeasonPrefix($season, $site, $url, $uri)) {
            return $redirect;
        }
        if ($redirect = $this->redirectWithoutPrefix($site, $url, $uri)) {
            return $redirect;
        }
        // // Enforce prefix, if applicable
        

        return App::make(Controller::class)->run($uri);
    }

    /**
     * findSite locates the site based on the current URL
     */
    protected function findSeason(string $rootUrl, string $url, $site)
    {
        $season = Season::getSeasonFromRequest($rootUrl, $url);
        if (!$season) {
            return null;
        }
        $site->isFallbackMatch = true;
        Season::applyActiveSeason($season);

        return $season;
    }


    /**
     * parseUri removes the prefix from a URL
     */
    protected function parseUris($season, $site, string $url): string
    {

        $url = $site ? $site->removeRoutePrefix($url) : $url;

        $url = $season ? $season->removeRoutePrefix($url) : $url;

        return $url;
    }

       /**
     * redirectWithoutPrefix redirects if a prefix is enforced
     */
    protected function redirectWithoutSeasonPrefix(SeasonDefinition $season, $site, string $originalUrl, string $proposedUrl)
    {
        $theme = Theme::getActiveTheme();
        $router = new Router($theme);
        if($router->findByUrl($proposedUrl)){

            $pageSeasonable = $router->findByUrl($proposedUrl)->settings['is_seasonable'] ?? false;
        }
        else{
            $pageSeasonable = false;
        }
        // Only a fallback site should redirect
        if (!$season || !$site || !$season->isFallbackMatch || !$pageSeasonable) {
            return null;
        }



        // A prefix has been found and removed already
        if ($originalUrl !== '/' && ltrim(str_replace(ltrim($site->route_prefix, '/'), '', $originalUrl), '/') !== $proposedUrl ) {
            return null;
        }


        // Apply redirect policy
        $redirectUrl = $this->determineRedirectFromPolicyExtended($season, $site, $originalUrl, $pageSeasonable);
        if (!$redirectUrl) {
            abort(404);
        }
        
        // Preserve query string
        if ($queryString = Request::getQueryString()) {
            $redirectUrl .= '?'.$queryString;
        }
        // No prefix detected, attach one with redirect
        return Redirect::to($redirectUrl, 301);
    }


    /**
     * determineRedirectFromPolicyExtended returns a site based on the configuration
     */
    protected function determineRedirectFromPolicyExtended($originalSeason, $originalSite, $originalUrl, $pageSeasonable)
    {
        $locales = $this->getLocalesFromBrowser((string) Request::server('HTTP_ACCEPT_LANGUAGE'));
        
        $url = ltrim($originalUrl, '/');

        $endOfUrl = ltrim(str_replace(ltrim($originalSite->route_prefix, '/'), '', $originalUrl), '/');
        dump('seasonable', $pageSeasonable);
        $season = ($pageSeasonable) ? $originalSeason->getAttributeTranslated('code', $originalSite->locale).'/' : '';

        return trim($originalSite->route_prefix.'/'. $season .$endOfUrl, '/');
        

    }
    
    /**
     * getLocalesFromBrowser based on an accepted string, e.g. en-GB,en-US;q=0.9,en;q=0.8
     * Returns a sorted array in format of `[(string) locale => (float) priority]`
     */
    public function getLocalesFromBrowser(string $acceptedStr): array
    {
        $result = $matches = [];
        $acceptedStr = strtolower($acceptedStr);

        // Find explicit matches
        preg_match_all('/([\w-]+)(?:[^,\d]+([\d.]+))?/', $acceptedStr, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $locale = $match[1] ?? '';
            $priority = (float) ($match[2] ?? 1.0);

            if ($locale) {
                $result[$locale] = $priority;
            }
        }

        // Estimate other locales by popping off the region (en-us -> en)
        foreach ($result as $locale => $priority) {
            $shortLocale = explode('-', $locale)[0];
            if ($shortLocale !== $locale && !array_key_exists($shortLocale, $result)) {
                $result[$shortLocale] = $priority - 0.1;
            }
        }

        arsort($result);
        return $result;
    }
    
}