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

        $response = $this->adjusted_json_response($translationKey->key);


        // return response()->json(
        //     $translationKey->load('translations.locale', 'tags'),
        //     201
        // );

        return response()->json($response, 201);
    }

    public function show($key)
    {
        $translationKey = TranslationKey::query()
            ->select('id', 'key', 'description') // <-- id is required for relation matching
            ->with([
                // include translation_key_id so Eloquent can attach translations to the parent
                'translations' => function ($q) {
                    $q->select('id', 'translation_key_id', 'locale_id', 'content');
                },
                'translations.locale' => function ($q) {
                    $q->select('id', 'code', 'name');
                },
                'tags' => function ($q) {
                    $q->select('id', 'name');
                },
            ])
            ->where('key', $key)
            ->firstOrFail();

        $response = [
            'key'         => $translationKey->key,
            'description' => $translationKey->description,
            'tags'        => $translationKey->tags->pluck('name')->toArray(),
            'translations' => $translationKey->translations->map(function ($t) {
                return [
                    'content' => $t->content,
                    'locale'  => $t->locale ? [
                        'code' => $t->locale->code,
                        'name' => $t->locale->name,
                    ] : null,
                ];
            })->toArray(),
        ];

        return response()->json($response, 200);
    }




    public function update(Request $request, $key)
    {
        $translationKey = TranslationKey::where('key', $key)->firstOrFail();

        $data = $request->validate([
            'description'    => 'nullable|string',
            'tags'           => 'nullable|array',
            'tags.*'         => 'string',
            'translations'   => 'nullable|array',
            'translations.*' => 'string',
        ]);

        if (isset($data['description'])) {
            $translationKey->update([
                'description' => $data['description']
            ]);
        }

        if (!empty($data['tags'])) {
            $tagIds = [];
            foreach ($data['tags'] as $tagName) {
                $tag = Tag::firstOrCreate(['name' => $tagName]);
                $tagIds[] = $tag->id;
            }
            $translationKey->tags()->sync($tagIds);
        }

        if (!empty($data['translations'])) {
            foreach ($data['translations'] as $localeCode => $content) {
                $locale = Locale::where('code', $localeCode)->first();
                if ($locale) {
                    $translation = $translationKey->translations()
                        ->where('locale_id', $locale->id)
                        ->first();

                    if ($translation) {
                        
                        $translation->update(['content' => $content]);
                    } else {
                        
                        Translation::create([
                            'translation_key_id' => $translationKey->id,
                            'locale_id'          => $locale->id,
                            'content'            => $content,
                        ]);
                    }
                }
            }
        }

        
        $response = $this->adjusted_json_response($translationKey->key);

        return response()->json($response, 200); 
    }



    public function adjusted_json_response($key)
    {
        $translationKey = TranslationKey::query()
            ->select('id', 'key', 'description') 
            ->with([
                
                'translations' => function ($q) {
                    $q->select('id', 'translation_key_id', 'locale_id', 'content');
                },
                'translations.locale' => function ($q) {
                    $q->select('id', 'code', 'name');
                },
                'tags' => function ($q) {
                    $q->select('id', 'name');
                },
            ])
            ->where('key', $key)
            ->firstOrFail();

        $response = [
            'key'         => $translationKey->key,
            'description' => $translationKey->description,
            'tags'        => $translationKey->tags->pluck('name')->toArray(),
            'translations' => $translationKey->translations->map(function ($t) {
                return [
                    'content' => $t->content,
                    'locale'  => $t->locale ? [
                        'code' => $t->locale->code,
                        'name' => $t->locale->name,
                    ] : null,
                ];
            })->toArray(),
        ];

        return $response;
    }

}
