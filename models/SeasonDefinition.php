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

    public $hasMany = [
        'seasonable' => [
            'Ladylain\Season\Models\Seasonable',
            'table' => 'ladylain_season_modelable_season',
            'key' => 'season_id'
        ]
    ];

    /**
     * afterSave
     */
    public function afterSave()
    {
        if( $this->is_primary)
        {
            // on désactive les autres
            self::where('id', '<>', $this->id)->update(['is_primary' => false]);
        }
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
     * for example en/blog → blog or https://domain.com/en/blog → https://domain.com/blog
     */
    public function removeRoutePrefix(string $url): string
    {
        
        if (!$this->code) {
            return $url;
        }

        // Parse URL to handle both relative and absolute URLs
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '';
        
        // Split path into segments
        $segments = explode('/', trim($path, '/'));
        $seasonCode = trim($this->code, '/');
        
        // Find and remove the season segment
        $filteredSegments = [];
        foreach ($segments as $segment) {
            if ($segment !== $seasonCode) {
                $filteredSegments[] = $segment;
            }
        }
        
        // Rebuild the path
        $path = implode('/', $filteredSegments);
        
        // If it's an absolute URL, reconstruct it
        if (isset($parsedUrl['scheme']) && isset($parsedUrl['host'])) {
            $result = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
            if (isset($parsedUrl['port'])) {
                $result .= ':' . $parsedUrl['port'];
            }
            $result .= '/' . ltrim($path, '/');
            if (isset($parsedUrl['query'])) {
                $result .= '?' . $parsedUrl['query'];
            }
            if (isset($parsedUrl['fragment'])) {
                $result .= '#' . $parsedUrl['fragment'];
            }
            return $result;
        }
        
        // For relative URLs, just return the path
        return $path;
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
