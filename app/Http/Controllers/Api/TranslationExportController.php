<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TranslationKey;

class TranslationExportController extends Controller
{
    public function export(Request $request)
    {
        $locales = $request->query('locale'); 
        $tags    = $request->query('tags');

        $query = TranslationKey::query()->select('id', 'key', 'description');

        // --- Filter by tags ---
        if (!empty($tags)) {
            $tagArray = array_map('trim', explode(',', $tags));
            $query->whereHas('tags', function ($q) use ($tagArray) {
                $q->whereIn('name', $tagArray);
            });
        }

        // --- Eager load relationships ---
        if ($locales) {
            $allowedLocales = array_map('trim', explode(',', $locales));

            $query->whereHas('translations.locale', function ($q) use ($allowedLocales) {
                $q->whereIn('code', $allowedLocales);
            });

            $query->with([
                'translations' => function ($q) use ($allowedLocales) {
                    $q->select('id', 'translation_key_id', 'locale_id', 'content')
                      ->whereHas('locale', function ($sub) use ($allowedLocales) {
                          $sub->whereIn('code', $allowedLocales);
                      });
                },
                'translations.locale:id,code,name',
                'tags:id,name'
            ]);
        } else {
            $query->with([
                'translations:id,translation_key_id,locale_id,content',
                'translations.locale:id,code,name',
                'tags:id,name'
            ]);
        }

        $keys = $query->get();

        // --- Clean response ---
        $keys->transform(function ($item) {
            // hide foreign keys in translations
            $item->translations->each(function ($t) {
                $t->makeHidden(['translation_key_id', 'locale_id']);
            });

            // hide pivot data in tags
            $item->tags->each(function ($tag) {
                $tag->makeHidden(['pivot']);
            });

            return $item;
        });

        return response()->json([
            'data' => $keys
        ]);
    }
}
