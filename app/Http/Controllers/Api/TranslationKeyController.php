<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TranslationKey;
use App\Models\Locale;
use App\Models\Tag;
use App\Models\Translation;

class TranslationKeyController extends Controller
{
    public function index(Request $request){

        $q = $request->query('q'); 
        $tag = $request->query('tag'); 
        $locale = $request->query('locale'); 
        $perPage = $request->query('per_page', 20);

        $query = TranslationKey::with(['translations.locale','tags']);

        if ($q) {
            $query->where('key', 'like', "%$q%")
                  ->orWhereHas('translations', fn($sq) => $sq->where('content','like', "%$q%"));
        }

        if ($tag) {
            $query->whereHas('tags', fn($tq) => $tq->where('name', $tag));
        }

        if ($locale) {
            $query->whereHas('translations.locale', fn($lq) => $lq->where('code', $locale));
        }

        return $query->paginate($perPage);
    }

    public function store(Request $request){
        $payload = $request->validate([
            'key' => 'required|string|unique:translation_keys,key',
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'translations' => 'nullable|array',
            'translations.*' => 'string',
        ]);

        $tk = TranslationKey::create(['key' => $payload['key'], 'description' => $payload['description'] ?? null]);

        // Create if not existss
        $tagIds = collect($payload['tags'] ?? [])->map(fn($t) => Tag::firstOrCreate(['name' => $t])->id)->toArray();
        $tk->tags()->sync($tagIds);

        // translations: expect ['en'=>'Hello','fr'=>'Bonjour']
        foreach($payload['translations'] ?? [] as $localeCode => $content) {
            $locale = Locale::firstWhere('code', $localeCode);
            if (!$locale) continue; // or create new locale if you want
            Translation::create([
                'translation_key_id' => $tk->id,
                'locale_id' => $locale->id,
                'content' => $content,
            ]);
        }

        return response()->json($tk->load('translations.locale','tags'), 201);
    }

    public function show(TranslationKey $key)
    {
        return $key->load('translations.locale','tags');
    }

    public function update(Request $request, TranslationKey $key)
    {
        $payload = $request->validate([
            'description' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'translations' => 'nullable|array',
            'translations.*' => 'string',
        ]);

        $key->update(['description' => $payload['description'] ?? $key->description]);

        if (isset($payload['tags'])) {
            $tagIds = collect($payload['tags'])->map(fn($t) => Tag::firstOrCreate(['name'=>$t])->id)->toArray();
            $key->tags()->sync($tagIds);
        }

        foreach($payload['translations'] ?? [] as $localeCode => $content) {
            $locale = Locale::firstWhere('code', $localeCode);
            if (!$locale) continue;
            $translation = $key->translations()->firstWhere('locale_id', $locale->id);
            if ($translation) {
                $translation->update(['content' => $content]);
            } else {
                Translation::create([
                    'translation_key_id' => $key->id,
                    'locale_id' => $locale->id,
                    'content' => $content,
                ]);
            }
        }

        return response()->json($key->fresh()->load('translations.locale','tags'));
    }

    public function destroy(TranslationKey $key)
    {
        $key->delete();
        return response()->noContent();
    }
}
