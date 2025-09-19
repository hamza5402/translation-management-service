<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TranslationKey;
use App\Services\TranslationVersion;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Arr;

class TranslationExportController extends Controller
{
    public function index(Request $request)
    {
        // Params:
        // locale= single locale code OR comma separated list (en,fr)
        // tags= comma separated tags (mobile,web)
        // nested= true/false -> convert dotted keys to nested objects

        $locales = $request->query('locale'); // null => all locales
        $tags = $request->query('tags');
        $nested = $request->boolean('nested', true);

        $localeKeyPart = $locales ? str_replace(',', '-', $locales) : 'all';
        $tagsPart = $tags ? str_replace(',', '-', $tags) : 'all';

        $version = TranslationVersion::get();
        $cacheKey = sprintf("translations_export:%s:%s:v%d", $localeKeyPart, $tagsPart, $version);

        // recommended: cache for short time but versioning ensures freshness
        $ttl = now()->addMinutes(60);

        $payload = Cache::remember($cacheKey, $ttl, function () use ($locales, $tags, $nested) {
            $query = TranslationKey::with(['translations.locale','tags']);

            if ($tags) {
                $tagArray = array_filter(array_map('trim', explode(',', $tags)));
                $query->whereHas('tags', function($q) use ($tagArray) {
                    $q->whereIn('name', $tagArray);
                });
            }

            $keys = $query->get();

            // build structure like { en: { 'auth.login': 'Login', ... }, fr: {...} } 
            $result = [];

            foreach ($keys as $k) {
                foreach ($k->translations as $t) {
                    if ($locales && !in_array($t->locale->code, explode(',', $locales))) continue;
                    $lc = $t->locale->code;
                    if (!isset($result[$lc])) $result[$lc] = [];
                    $result[$lc][$k->key] = $t->content;
                }
            }

            if ($nested) {
                // convert dotted keys to nested arrays for each locale
                $nestedResult = [];
                foreach ($result as $lc => $pairs) {
                    $nestedResult[$lc] = [];
                    foreach ($pairs as $flatKey => $val) {
                        Arr::set($nestedResult[$lc], $flatKey, $val);
                    }
                }
                return $nestedResult;
            }

            return $result;
        });

        return response()->json($payload);
    }
}
