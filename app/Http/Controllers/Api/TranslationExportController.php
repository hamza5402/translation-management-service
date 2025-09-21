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

        
        $locales = $request->query('locale'); 
        $tags    = $request->query('tags');

        
        $query = TranslationKey::with(['translations.locale', 'tags']);

        
        if (!empty($tags)) {
            $tagArray = array_map('trim', explode(',', $tags));
            $query->whereHas('tags', function ($q) use ($tagArray) {
                $q->whereIn('name', $tagArray);
            });
        }

        $keys = $query->get();

      
        $result = [];
        $allowedLocales = $locales ? explode(',', $locales) : null;

        foreach ($keys as $key) {
            foreach ($key->translations as $translation) {
                $code = $translation->locale->code;

                
                if ($allowedLocales && !in_array($code, $allowedLocales)) {
                    continue;
                }

                if (!isset($result[$code])) {
                    $result[$code] = [];
                }

                $result[$code][$key->key] = $translation->content;
            }
        }

        return response()->json($result);
    }


}
