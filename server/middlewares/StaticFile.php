<?php

namespace server\middlewares;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

/**
 * Static file Middleware
 */
class StaticFile implements MiddlewareInterface
{
    public string $publicPath;
    public array $staticFileExtensions = [
        "css", "js", "json", "xml", "html",
        "svg", "ttf", "eot", "otf", "woff", "woff2", "xap", "apk",
        "png", "jpg", "jpeg", "gif", "bmp", "webp", "ico",
        "flv", "swf", "mkv", "avi", "rm", "rmvb", "mpeg", "mpg",
        "ogg", "ogv", "mov", "wmv", "mp4", "webm", "mp3", "wav", "mid",
        "rar", "zip", "tar", "gz", "7z", "bz2", "cab", "iso",
        "doc", "docx", "xls", "xlsx", "ppt", "pptx", "pdf", "txt", "md"
    ];

    public function __construct(string $publicPath, ?array $staticFileExtensions = null)
    {
        $this->publicPath = $publicPath;
        if ($staticFileExtensions !== null) {
            $this->staticFileExtensions = $staticFileExtensions;
        }
    }

    public function process(Request $request, callable $next): Response
    {
        $file = $this->publicPath . $request->path();

        // Deny access hidden file
        if (str_contains($file, '/.') || str_contains($file, '..')) {
            return _json(403, ['code' => 403, 'message' => 'Forbidden']);
        }

        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (!$extension) {
            return $next($request);
        }
        if (!in_array($extension, $this->staticFileExtensions)) {
            return _json(403, ['code' => 403, 'message' => 'Forbidden']);
        }
        if (is_file($file)) {
            $response = new Response();
            $response->withFile($file);
            return $response;
        }
        return _json(404, ['code' => 404, 'message' => 'Not Found']);
    }
}
