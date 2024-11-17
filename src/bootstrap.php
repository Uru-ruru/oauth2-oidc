<?php

use Uru\BitrixCacher\Cache;
use Uru\BitrixCacher\CacheBuilder;
use Uru\DotEnv\DotEnv;

define('PROJECT_PATH', dirname(__DIR__).'/');

function project_path($path = ''): string
{
    return PROJECT_PATH.'/'.$path;
}

function app_path($path = ''): string
{
    return project_path("src/{$path}");
}

/**
 * @return CacheBuilder|mixed
 */
function cache(?string $key = null, ?float $minutes = null, ?Closure $callback = null, string $initDir = '/', string $basedir = 'cache'): mixed
{
    if (0 === func_num_args()) {
        return new CacheBuilder();
    }

    return Cache::remember($key, $minutes, $callback, $initDir, $basedir);
}

function env($key, $default = null)
{
    return DotEnv::get($key, $default);
}

require_once project_path('vendor/autoload.php');

DotEnv::load(project_path('config/.env.php'));
