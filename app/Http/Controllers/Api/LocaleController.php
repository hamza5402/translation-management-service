<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Locale;
use Illuminate\Http\JsonResponse;

class LocaleController extends Controller
{
    /**
     * List all locales.
     */
    public function index(): JsonResponse
    {
        $locales = Locale::orderBy('code')->get();
        return response()->json($locales);
    }

    /**
     * Create a new locale.
     *
     * Request body:
     * {
     *   "code": "it",
     *   "name": "Italian"
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        
        if (isset($data['code'])) {
            $data = [$data];
        }

        $inserted = [];
        $skipped = [];

        foreach ($data as $localeData) {
            $validator = \Validator::make($localeData, [
                'code' => ['required', 'string', 'max:10'],
                'name' => ['nullable', 'string', 'max:100'],
            ]);

            if ($validator->fails()) {
                $skipped[] = [
                    'input' => $localeData,
                    'errors' => $validator->errors(),
                ];
                continue;
            }

   
            if (Locale::where('code', strtolower($localeData['code']))->exists()) {
                $skipped[] = [
                    'input' => $localeData,
                    'reason' => 'Already exists',
                ];
                continue;
            }

            $locale = Locale::create([
                'code' => strtolower($localeData['code']),
                'name' => $localeData['name'] ?? null,
            ]);

            $inserted[] = $locale;
        }

        return response()->json([
            'inserted' => $inserted,
            'skipped' => $skipped,
        ], 201);
    }


}
