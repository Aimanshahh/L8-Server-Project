<?php namespace App\Http\Middleware;

use Closure;
use App;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RefreshToken {

    const TOKEN = "841ccd1b64330373d0409d2d2002b7b8";

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        
        if (!($response instanceof BinaryFileResponse))
            $response->header('X-Refresh-Token', self::TOKEN);

        return $response;
    }

}
