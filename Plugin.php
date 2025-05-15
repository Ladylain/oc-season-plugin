<?php namespace Ladylain\Season;

use Season;
use App;
use Backend;
use Config;
use Cms\Classes\ThisVariable;
use System\Classes\PluginBase;
use Event;
use Request;
use Site;
use Ladylain\Season\Classes\SeasonCmsController;
use Ladylain\Season\Helpers\Season as HelpersSeason;
use Cms\Classes\Page;

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
            
            $controller->vars['this'] = new ThisVariable([
                'controller' => $controller,
                'page' => $controller->getPage(),
                'layout' => $controller->getLayout(),
                'theme' => $controller->getTheme(),
                'param' => $controller->getRouter()->getParameters(),
                'environment' => fn() => App::environment(),
                'request' => fn() => App::make('request'),
                'session' => fn() => App::make('session')->driver(),
                'site' => fn() => Site::getActiveSite(),
                'locale' => fn() => App::getLocale(),
                'season' => fn() => Season::getActiveSeason(),
    
                // @deprecated
                'method' => fn() => Request::method(),
            ]);
            
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
}
