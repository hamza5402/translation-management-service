<?php

namespace App\Services;

class TranslationVersion
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    const CACHE_KEY = 'translations_version';

    public static function get(): int
    {
        return (int) Cache::get(self::CACHE_KEY, 1);
    }

    public static function bump(): int
    {
        if (!Cache::has(self::CACHE_KEY)) Cache::put(self::CACHE_KEY, 1);
        return Cache::increment(self::CACHE_KEY);
    }    
}
