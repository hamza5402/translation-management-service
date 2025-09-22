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
    $search   = $request->query('q');
    $tag      = $request->query('tag');
    $locale   = $request->query('locale');
    $perPage  = $request->query('per_page', 20);

    $query = TranslationKey::query()
        ->select('id', 'key', 'description')
        ->with([
            // Need translation_key_id so Eloquent can attach translations to the parent
            'translations:id,translation_key_id,locale_id,content',
            'translations.locale:id,code,name',
            'tags:id,name'
        ]);

    if ($search) {
        $query->where(function($q) use ($search) {
            $q->where('key', 'like', "%{$search}%")
              ->orWhereHas('translations', function($sub) use ($search) {
                  $sub->where('content', 'like', "%{$search}%");
              });
        });
    }

    if ($tag) {
        $query->whereHas('tags', function($sub) use ($tag) {
            $sub->where('name', $tag);
        });
    }

    if ($locale) {
        $query->whereHas('translations.locale', function($sub) use ($locale) {
            $sub->where('code', $locale);
        });
    }

    $paginated = $query->paginate($perPage);

    // Hide unwanted fields in nested relations before returning
    $paginated->getCollection()->transform(function ($item) {
        // hide translation foreign keys
        $item->translations->each(function ($t) {
            $t->makeHidden(['translation_key_id', 'locale_id']);
        });

        // hide pivot data inside tags
        $item->tags->each(function ($tag) {
            $tag->makeHidden(['pivot']);
        });

        return $item;
    });

    return $paginated;
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
            'description'  => 'nullable|string',
            'tags'         => 'nullable|array',
            'tags.*'       => 'string',
            'translations' => 'nullable|array',
            'translations.*' => 'string',
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
