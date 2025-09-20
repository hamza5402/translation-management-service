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
        $data = $request->validate([
            'code' => ['required', 'string', 'max:10', 'unique:locales,code'],
            'name' => ['nullable', 'string', 'max:100'],
        ]);

        $locale = Locale::create([
            'code' => strtolower($data['code']),
            'name' => $data['name'] ?? null,
        ]);

        return response()->json($locale, 201);
    }
}
