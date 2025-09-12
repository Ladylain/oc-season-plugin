<?php namespace Ladylain\Season\Components;

use Season;
use Cms\Classes\ComponentBase;
use Site;
use Cms;
use Event;
use Cms\Classes\Page;
use Exception;
use Ladylain\Season\Classes\SeasonManager;

/**
 * SeasonLocalePicker Component
 *
 * @link https://docs.octobercms.com/3.x/extend/cms-components.html
 */
class SeasonLocalePicker extends ComponentBase
{
    /**
     * @var array sitesCache for multiple calls
     */
    protected $sitesCache;

    /**
     * @var array allSitesCache for multiple calls
     */
    protected $allSitesCache;

    public function componentDetails()
    {
        return [
            'name' => 'Season Locale Picker',
            'description' => 'A component to change Locale with season on frontend.'
        ];
    }

    /**
     * isEnabled returns true if the site picker should be displayed
     */
    public function isEnabled()
    {
        return Site::hasMultiSite();
    }

    /**
     * sites lazily loads the available sites
     */
    public function sites()
    {
        return $this->sitesCache ??= $this->allSites()->inSiteGroup();
    }

    /**
     * allSites lazily loads the available sites
     */
    public function allSites()
    {
        if ($this->allSitesCache !== null) {
            return $this->allSitesCache;
        }
        
        $sites = Site::listEnabled();
        $seasonManager = app('ladylain.seasons');

        foreach ($sites as $site) {
            // D'abord, on génère l'URL de base avec le préfixe de langue via Cms::siteUrl()
            $baseUrl = Cms::siteUrl(
                $this->getPage(),
                $site,
                $this->getRouter()->getParameters()
            );
            
            // Si la page est seasonable, on modifie l'URL pour y injecter la saison
            if ($this->getPage()->isSeasonable()) {
                $activeSeason = $seasonManager->getActiveSeason();
                if ($activeSeason) {
                    // Parse l'URL pour obtenir les parties
                    $parsedUrl = parse_url($baseUrl);
                    $path = $parsedUrl['path'] ?? '';
                    
                    // Retire le préfixe du site du chemin
                    $cleanPath = $site->removeRoutePrefix($path);
                    
                    // Ajoute le code de la saison (traduit selon la locale du site)
                    $seasonCode = $activeSeason->getAttributeTranslated('code', $site->locale);
                    
                    // Reconstruit le chemin avec site prefix + season + path
                    $newPath = $site->attachRoutePrefix($seasonCode . $cleanPath);
                    
                    // Reconstruit l'URL complète
                    $urlWithSeason = (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '') .
                                   (isset($parsedUrl['host']) ? $parsedUrl['host'] : '') .
                                   (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '') .
                                   '/' . ltrim($newPath, '/') .
                                   (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '') .
                                   (isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '');
                    
                    $site->setUrlOverride($urlWithSeason);
                } else {
                    $site->setUrlOverride($baseUrl);
                }
            } else {
                $site->setUrlOverride($baseUrl);
            }
        }

        return $this->allSitesCache = $sites;
    }

    /**
     * pageSites returns a CMS page for all available sites
     */
    public function pageSites($pageName, $params = [])
    {
        try {
            $page = Page::loadCached($this->getTheme(), $pageName);
            if (!$page) {
                return [];
            }
        }
        catch (Exception $ex) {
            return [];
        }

        $sites = Site::listEnabled();
        $seasonManager = app('ladylain.seasons');

        foreach ($sites as $site) {
            // D'abord, on génère l'URL de base avec le préfixe de langue via Cms::siteUrl()
            $baseUrl = Cms::siteUrl($page, $site, (array) $params);
            
            // Si la page est seasonable, on modifie l'URL pour y injecter la saison
            if ($page->isSeasonable()) {
                $activeSeason = $seasonManager->getActiveSeason();
                if ($activeSeason) {
                    // Parse l'URL pour obtenir les parties
                    $parsedUrl = parse_url($baseUrl);
                    $path = $parsedUrl['path'] ?? '';
                    
                    // Retire le préfixe du site du chemin
                    $cleanPath = $site->removeRoutePrefix($path);
                    
                    // Ajoute le code de la saison (traduit selon la locale du site)
                    $seasonCode = $activeSeason->getAttributeTranslated('code', $site->locale);
                    
                    // Reconstruit le chemin avec site prefix + season + path
                    $newPath = $site->attachRoutePrefix($seasonCode . $cleanPath);
                    
                    // Reconstruit l'URL complète
                    $urlWithSeason = (isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '') .
                                   (isset($parsedUrl['host']) ? $parsedUrl['host'] : '') .
                                   (isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '') .
                                   '/' . ltrim($newPath, '/') .
                                   (isset($parsedUrl['query']) ? '?' . $parsedUrl['query'] : '') .
                                   (isset($parsedUrl['fragment']) ? '#' . $parsedUrl['fragment'] : '');
                    
                    $site->setUrlOverride($urlWithSeason);
                } else {
                    $site->setUrlOverride($baseUrl);
                }
            } else {
                $site->setUrlOverride($baseUrl);
            }
        }

        return $sites;
    }
}