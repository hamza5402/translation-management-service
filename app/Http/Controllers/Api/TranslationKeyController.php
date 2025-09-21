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

    public function index(Request $request)
    {
        

        // Get query parameters
        $search   = $request->query('q'); 
        $tag      = $request->query('tag'); 
        $locale   = $request->query('locale'); 
        $perPage  = $request->query('per_page', 20);

        // Base query with relationships
        $query = TranslationKey::with(['translations.locale', 'tags']);

        // Search by key or translation content
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('key', 'like', "%{$search}%")
                  ->orWhereHas('translations', function ($subQuery) use ($search) {
                      $subQuery->where('content', 'like', "%{$search}%");
                  });
            });
        }

        // Filter by tag
        if (!empty($tag)) {
            $query->whereHas('tags', function ($subQuery) use ($tag) {
                $subQuery->where('name', $tag);
            });
        }

        // Filter by locale
        if (!empty($locale)) {
            $query->whereHas('translations.locale', function ($subQuery) use ($locale) {
                $subQuery->where('code', $locale);
            });
        }

        // Paginate results
        return $query->paginate($perPage);
    }


    public function store(Request $request)
    {
        
        // Validate request
        $data = $request->validate([
            'key'          => ['required', 'string', 'unique:translation_keys,key'],
            'description'  => ['nullable', 'string'],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string'],
            'translations' => ['required', 'array'],
            'translations.*' => ['required', 'string'],
        ]);


        $translationKey = TranslationKey::create([
            'key'         => $data['key'],
            'description' => $data['description'] ?? null,
        ]);


        $tagIds = [];
        if (!empty($data['tags'])) {
            foreach ($data['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
        }
        $translationKey->tags()->sync($tagIds);

        // Handle Translations (like ['en'=>'Hello','fr'=>'Bonjour'])
        if (!empty($data['translations'])) {
            foreach ($data['translations'] as $localeCode => $content) {
                $locale = Locale::where('code', $localeCode)->first();
                if ($locale) {
                    Translation::create([
                        'translation_key_id' => $translationKey->id,
                        'locale_id'          => $locale->id,
                        'content'            => $content,
                    ]);
                }
            }
        }

        // Return JSON response with relations
        return response()->json(
            $translationKey->load('translations.locale', 'tags'),
            201
        );
    }


    public function show(TranslationKey $key)
    {
        
        return $key->load('translations.locale','tags');
    }

    public function update(Request $request, TranslationKey $key)
    {
        
        // Validate request
        $data = $request->validate([
            'key'          => ['required', 'string', 'unique:translation_keys,key'],
            'description'  => ['nullable', 'string'],
            'tags'         => ['nullable', 'array'],
            'tags.*'       => ['string'],
            'translations' => ['required', 'array'],
            'translations.*' => ['required', 'string'],
        ]);

        // Update description if provided
        if (isset($data['description'])) {
            $key->update([
                'description' => $data['description']
            ]);
        }

        // Update Tags
        if (!empty($data['tags'])) {
            $tagIds = [];
            foreach ($data['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $key->tags()->sync($tagIds);
        }

        // Update Translations
        if (!empty($data['translations'])) {
            foreach ($data['translations'] as $localeCode => $content) {
                $locale = Locale::where('code', $localeCode)->first();
                if ($locale) {
                    $translation = $key->translations()
                                       ->where('locale_id', $locale->id)
                                       ->first();

                    if ($translation) {
                        // Update existing translation
                        $translation->update(['content' => $content]);
                    } else {
                        // Create new translation
                        Translation::create([
                            'translation_key_id' => $key->id,
                            'locale_id'          => $locale->id,
                            'content'            => $content,
                        ]);
                    }
                }
            }
        }

        // Return updated data with relations
        return response()->json(
            $key->fresh()->load('translations.locale', 'tags')
        );
    }


    public function destroy(TranslationKey $key)
    {
        $key->delete();
        return response()->noContent();
    }
}
