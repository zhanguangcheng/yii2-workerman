<?php

namespace server\middlewares;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

/**
 * Guard Middleware
 */
class Guard implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        static $ua = '/(scrapy|python|java|httpclient|okhttp|HTTrack|harvest|audit|dirbuster|pangolin|nmap|sqln|-scan|hydra|Parser|libwww|BBBike|sqlmap|w3af|owasp|Nikto|fimap|havij|PycURL|zmeu|BabyKrokodil|netsparker|httperf|bench|curl)/';
        $userAgent = $request->header('user-agent');
        if (!$userAgent || preg_match($ua, $userAgent)) {
            return _json(403, ['code' => 403, 'message' => 'Forbidden ua']);
        }

        static $uri = '/\/\.|union(.*?)select|select.+(from|limit|having)|into(\s+)+(?:dump|out)file\s*|(?:base64_decode|define|eval|file_get_contents|include|require|require_once|shell_exec|phpinfo|system|passthru|preg_\\w+|execute|echo|print|print_r|var_dump|(fp)open|alert|showmodaldialog)\(|\$_(GET|post|cookie|files|session|env|phplib|GLOBALS|SERVER)\[|<(iframe|script|body|img|layer|div|meta|style|base|object|input)|(onmouseover|onerror|onload)=/';
        if (preg_match($uri, $request->uri())) {
            return _json(403, ['code' => 403, 'message' => 'Forbidden uri']);
        }

        /** @var Response $response */
        $response = $next($request);

        // Hidden server header
        $response->withHeader('Server', '*');

        return $response;
    }
}
