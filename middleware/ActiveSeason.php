<?php namespace Ladylain\Season\Middleware;

use Closure;
use Season;
use Url;

/**
 * ActiveSite sets the active site based on the request parameters
 */
class ActiveSeason
{
    /**
     * handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $season = Season::getSeasonFromRequest($request->root(), $this->getRoutedUri($request));

        if ($season) {
            Season::applyActiveSeason($season);
        }
        
        return $next($request);
    }

    /**
     * getRoutedUri
     */
    protected function getRoutedUri($request)
    {
        $rootUri = trim(parse_url(Url::to(''), PHP_URL_PATH), '/');
        $fullUri = trim(parse_url($request->fullUrl(), PHP_URL_PATH), '/');
        return $rootUri === "" || starts_with($fullUri, $rootUri)
            ? trim(substr($fullUri, strlen($rootUri)), '/')
            : $fullUri;
    }
}