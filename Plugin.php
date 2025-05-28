<?php namespace Ladylain\Season;

use Season;
use App;
use Backend;
use Cms\Classes\CmsException;
use Cms\Classes\Controller;
use Config;
use Cms\Classes\ThisVariable;
use System\Classes\PluginBase;
use Event;
use Request;
use Site;
use Ladylain\Season\Classes\SeasonCmsController;
use Ladylain\Season\Helpers\Season as HelpersSeason;
use Cms\Classes\Page;
use Cms\Classes\Router;
use Cms\Classes\Theme;
use Lang;
use Media\Classes\MediaLibrary;
use October\Rain\Database\Relations\Relation;
use Route;
use Url;

/**
 * Plugin Information File
 *
 * @link https://docs.octobercms.com/3.x/extend/system/plugins.html
 */
class Plugin extends PluginBase
{
    /**
     * pluginDetails about this plugin.
     */
    public function pluginDetails()
    {
        return [
            'name' => 'Season',
            'description' => 'A plugin for managing seasons',
            'author' => 'Lucas Palomba',
            'icon' => 'icon-leaf'
        ];
    }

    /**
     * register method, called when the plugin is first registered.
     */
    public function register()
    {
        $this->registerSingletons();
        $this->registerFacades();
        
    }


    /**
     * registerSingletons
     */
    protected function registerSingletons()
    {
        $this->app->singleton('ladylain.seasons', \Ladylain\Season\Classes\SeasonManager::class);
    }
    
    /**
     * registerFacades
     */
    public function registerFacades(){
        \Illuminate\Foundation\AliasLoader::getInstance()->alias('Season', HelpersSeason::class);
    }

    /**
     * boot method, called right before the request route.
     */
    public function boot()
    {
        Relation::morphMap([
            'page' => \LucasPalomba\PageTree\Models\Page::class,
        ]);
        
        //$this->extendGlobalMiddlewares();

        Event::listen('cms.route', function () {
            \Route::any('{slug?}', [SeasonCmsController::class, 'run'])
            ->where('slug', '(.*)?')
            ->middleware(Config::get('cms.middleware_group', 'web'))
            ;
        });
        Page::extend(function($page)  {
            $page->addDynamicMethod('isSeasonable', function() use($page) {
                return $page->settings['is_seasonable'] ?? false;
            });
        });
        \Event::listen('cms.page.init', function($controller) {  
            $controller->vars['this']->config['season'] = fn() => Season::getActiveSeason();
        });
        
        if(App::runningInBackend()){
            \Event::listen('cms.template.extendTemplateSettingsFields', function ($extension, $dataHolder) {
                if ($dataHolder->templateType === 'page') {
                    $dataHolder->settings['seasons'] = [
                            'property' => 'is_seasonable',
                            'title' => 'Saisonnable',
                            'description' => 'Cette page est saisonnable',
                            'type' => 'checkbox',
                            'tab' => 'Saisons',
                        ];
                }
            });

            \Event::listen('backend.page.beforeDisplay', function ($controller, $action, $params) {
                $site = Site::getEditSite();
                if($site->id == 1) {
                    // Si on est dans le backend, on utilise le dossier media par défaut
                    Config::set('filesystems.disks.media.root', storage_path('app/media'));
                    MediaLibrary::instance()->resetCache();
                } else {
                    // Sinon, on utilise le dossier media du site actif
                    Config::set('filesystems.disks.media.root', storage_path('app/media/uploaded-files'));
                    MediaLibrary::instance()->resetCache();
                }
            });
        }

    
    }


    public function extendGlobalMiddlewares(){
        // \Cms\Classes\CmsController::extend(function($controller) {
        //     $controller->middleware(\Ladylain\Season\Middleware\ActiveSeason::class);
        // });
        $this->app[\Illuminate\Contracts\Http\Kernel::class]
        ->appendMiddlewareToGroup('web', \Ladylain\Season\Middleware\ActiveSeason::class);
    }

    /**
     * registerComponents used by the frontend.
     */
    public function registerComponents()
    {
        return [
            'Ladylain\Season\Components\SeasonPicker' => 'seasonPicker',
        ];
    }

    /**
     * registerPermissions used by the backend.
     */
    public function registerPermissions()
    {
        return []; // Remove this line to activate
    }

    /**
     * registerSettings used by the backend.
     */
    public function registerSettings()
    {
        return [
            'seasons' => [
                'label' => 'Definitions de saisons',
                'url' => Backend::url('ladylain/season/seasons'),
                'description' => 'Gérer les saisons disponibles pour cette application.',
                'category' => 'CATEGORY_SYSTEM',
                'icon' => 'icon-leaf',
                'permissions' => ['ladylain.season.*'],
                'order' => 500,
            ],
        ];
    }

    /**
     * registerMarkupTags used by the frontend.
     */
    public function registerMarkupTags()
    {
        return [
            'filters' => [
                'page' => [$this, 'customPageFilter'],
            ]
        ];
    }


    public function customPageFilter($name, $parameters = [], $routePersistence = true)
    {
        $controller = new Controller();

        if ($name instanceof ThisVariable) {
            $name = '';
        }

        if (!$name) {
            return $controller->currentPageUrl($parameters, $routePersistence);
        }
        
        // Invalid input same as not found
        if (!is_string($name)) {
            return null;
        }
        
        // Second parameter can act as third
        if (is_bool($parameters)) {
            $routePersistence = $parameters;
        }
        
        if (!is_array($parameters)) {
            $parameters = [];
        }
        $theme = Theme::getActiveTheme();
        if (!$theme) {
            throw new CmsException(Lang::get('cms::lang.theme.active.not_found'));
        }
        $router = new Router($theme);
        if ($routePersistence) {
            $parameters = array_merge($router->getParameters(), $parameters);
        }
        
        if (!$url = $router->findByFile($name, $parameters)) {
            return null;
        }
        // get Page Model
        $page = $router->findByUrl($url);
        // If the page is not found, return null
        if(!$page) {
            return null;
        }
        // If the page is not seasonable, return the url
        if($page->isSeasonable()){
            $season = Season::getActiveSeason();
            if($season){
                $url = Season::getUrlPrefix().$url;
            }
        }
        // else, prefix with site route prefix
        else{
            $site = Site::getActiveSite();
            if($site->is_prefixed){
                $url = $site->route_prefix.$url;
            }
        }

        $path = $url;

        // Process path
        if (substr($path, 0, 1) === '/') {
            $path = substr($path, 1);
        }
        
        // Use the router
        $routeAction = 'Cms\Classes\SeasonCmsController@run';

        $actionExists = Route::getRoutes()->getByAction($routeAction) !== null;
        
        if ($actionExists) {
            $result = Url::action($routeAction, ['slug' => $path]);
        }
        else {
            $result = $path;
        }
        // Use the base URL
        return Url::toRelative($result);
        
    }
    
}
