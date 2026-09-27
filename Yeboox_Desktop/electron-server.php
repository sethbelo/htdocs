<?php

// Minimal router for PHP's embedded server. It avoids the extra Artisan
// process used by `artisan serve` and serves public assets directly.
$publicPath = __DIR__.DIRECTORY_SEPARATOR.'public';
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$asset = realpath($publicPath.$uri);

if ($uri !== '/' && $asset !== false && str_starts_with($asset, realpath($publicPath))) {
    return false;
}

require $publicPath.DIRECTORY_SEPARATOR.'index.php';
