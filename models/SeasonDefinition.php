<?php namespace Ladylain\Season\Models;

use Model;
use Season;
use Ladylain\Season\Classes\SeasonCollection;

/**
 * Season Model
 *
 * @link https://docs.octobercms.com/3.x/extend/system/models.html
 */
class SeasonDefinition extends Model
{
    use \October\Rain\Database\Traits\Validation;
    
    public $implement = [
        '@'.\RainLab\Translate\Behaviors\TranslatableModel::class
    ];

    /**
     * @var string table name
     */
    public $table = 'ladylain_season_seasons';

    /**
     * @var array rules for validation
     */
    public $rules = [];

    public $translatable = ['code'];

        /**
     * afterSave
     */
    public function afterSave()
    {
        Season::resetCache();
    }

    /**
     * getStatusCodeOptions
     */
    public function getStatusCodeOptions()
    {
        return [
            1 => ['Enabled', '#85CB43'],
            0 => ['Disabled', '#bdc3c7']
        ];
    }

    /**
     * getDropdownOptions
     */
    public static function getDropdownOptions()
    {
        return self::orderBy('name')->get()->pluck('name', 'id')->toArray();
    }

    /**
     * setUrlOverride
     */
    public function setUrlOverride(string $url)
    {
        $this->url = $url;
    }

        /**
     * matchesBaseUri matches a URI , if specified
     */
    public function matchesBaseUri(string $uri): bool
    {
        return strpos($uri, $this->code) !== false;
    }

            /**
     * removeRoutePrefix removes the route prefix from a uri,
     * for example en/blog → blog
     */
    public function removeRoutePrefix(string $url): string
    {
        if (!$this->code) {
            return $url;
        }

        $url = ltrim($url, '/');
        $prefix = ltrim($this->code, '/');

        if (substr($url, 0, strlen($prefix)) === $prefix) {
            $url = substr($url, strlen($prefix));
        }

        return $url;
    }

    /**
     * newCollection instance.
     * @return \Ladylain\Season\Classes\SeasonCollection
     */
    public function newCollection(array $models = [])
    {
        return new SeasonCollection($models);
    }
}
