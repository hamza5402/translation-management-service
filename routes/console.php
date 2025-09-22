<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\TranslationKeyController;


Artisan::command('seed:translations {count=1000}', function (int $count) {
    $controller = app(TranslationKeyController::class);

    $locales = ["en", "fr", "es", "de", "it", "pt", "ru", "zh", "ja", "ar"];
    $tagsPool = ["web", "mobile"];

    for ($i = 1; $i <= $count; $i++) {
        $tags = collect($tagsPool)->random(rand(1, count($tagsPool)))->values()->toArray();
        $chosenLocales = collect($locales)->random(rand(1, count($locales)))->values()->toArray();
        $translations = [];
        foreach ($chosenLocales as $locale) {
            $translations[$locale] = "Login (" . strtoupper($locale) . ")";
        }

        $request = Request::create('/api/v1/keys', 'POST', [
            'key'          => "auth.login.button{$i}",
            'description'  => "Login button label {$i}",
            'tags'         => $tags,
            'translations' => $translations,
        ]);

        $response = $controller->store($request);

        if ($response->status() === 201) {
            $this->info("Inserted: auth.login.button{$i}");
        } else {
            $this->warn("Skipped: auth.login.button{$i}");
        }
    }
})->purpose('Seed fake translations into the system');
