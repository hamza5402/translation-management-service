<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TranslationKeyController;

class SeedTranslations extends Command
{
    protected $signature = 'seed:translations {count=1000}';
    protected $description = 'Seed fake translations using TranslationKeyController@store';

    public function handle(): void
    {
        $count = (int)$this->argument('count');
        $controller = app(TranslationKeyController::class);

        $locales = ["en", "fr", "es", "de", "it", "pt", "ru", "zh", "ja", "ar"];
        $tagsPool = ["web", "mobile"];

        for ($i = 1; $i <= $count; $i++) {
            // Random tags
            $tags = collect($tagsPool)->random(rand(1, count($tagsPool)))->values()->toArray();

            // Random locales
            $chosenLocales = collect($locales)->random(rand(1, count($locales)))->values()->toArray();

            // Build translations
            $translations = [];
            foreach ($chosenLocales as $locale) {
                $translations[$locale] = "Login (" . strtoupper($locale) . ")";
            }

            // Fake request
            $request = Request::create('/api/v1/keys', 'POST', [
                'key'          => "auth.login.button{$i}",
                'description'  => "Login button label {$i}",
                'tags'         => $tags,
                'translations' => $translations,
            ]);

            // Pass request into controller
            $response = $controller->store($request);

            if ($response->status() === 201) {
                $this->info("✅ Inserted: auth.login.button{$i}");
            } else {
                $this->error("⚠️ Failed at: auth.login.button{$i}");
            }
        }
    }
}
