<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Translation;
use App\Models\TranslationKey;
use App\Models\Tag;
use App\Models\Locale;
use App\Services\TranslationVersion;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Translation::class, TranslationKey::class, Tag::class, Locale::class] as $model) {

            $model::saved(
                fn($m) => TranslationVersion::bump()
            );

            $model::deleted(
                fn($m) => TranslationVersion::bump()
            );
        }
    }
}
