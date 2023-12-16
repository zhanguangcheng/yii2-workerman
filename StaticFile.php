<?php

namespace app;

use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;

/**
 * Static file processor
 */
class StaticFile
{
    public static string $publicPath = __DIR__ . '/web';
    public static array $blackExtensions = ['php', 'db', 'mdb', 'sqlite', 'sqlite3', 'jar'];
    public static array $staticFileExtensions = [
        "css", "js", "json", "xml", "html",
        "svg", "ttf", "eot", "otf", "woff", "woff2", "xap", "apk",
        "png", "jpg", "jpeg", "gif", "bmp", "webp", "ico",
        "flv", "swf", "mkv", "avi", "rm", "rmvb", "mpeg", "mpg",
        "ogg", "ogv", "mov", "wmv", "mp4", "webm", "mp3", "wav", "mid",
        "rar", "zip", "tar", "gz", "7z", "bz2", "cab", "iso",
        "doc", "docx", "xls", "xlsx", "ppt", "pptx", "pdf", "txt", "md"
    ];

    public static function process(Request $request): Response|null
    {
        $file = self::$publicPath . $request->path();

        // Deny access hidden file
        if (str_contains($file, '/.') || str_contains($file, '..')) {
            return new Response(403, [], '403 Forbidden');
        }

        // Deny access black list extensions file
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array($extension, self::$blackExtensions)) {
            return new Response(404, [], '404 Not Found');
        }

        if (is_file($file)) {
            $response = new Response();
            $response->withFile($file);
            return $response;
        }

        // Response code 404 is returned if a static file does not exist
        if (in_array($extension, self::$staticFileExtensions)) {
            return new Response(404, [], '404 Not Found');
        }
        return null;
    }
}
