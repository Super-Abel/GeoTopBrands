<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    /**
     * List of supported country codes (ISO-2) and their configurations
     */
    private const SUPPORTED_COUNTRIES = [
        'FR' => [
            'name' => 'France',
            'bonus' => '200% up to €500 + 500 Free Spins'
        ],
        'GB' => [
            'name' => 'United Kingdom',
            'bonus' => '100% up to £500 + 20 Free Spins'
        ],
        'DE' => [
            'name' => 'Germany',
            'bonus' => '150% up to €1000 + 100 Free Spins'
        ],
        'ES' => [
            'name' => 'Spain',
            'bonus' => '100% up to €300 + 50 Free Spins'
        ],
        'IT' => [
            'name' => 'Italy',
            'bonus' => '125% up to €800 + 75 Free Spins'
        ]
    ];

    /**
     * Default country configuration when geolocation fails or country not supported
     */
    private const DEFAULT_COUNTRY = [
        'name' => 'International',
        'bonus' => '100% up to $200 + 25 Free Spins'
    ];

    /**
     * Get user's country from Cloudflare header with fallback options
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    private function getUserCountryConfig(Request $request): array
    {
        // Get country code from Cloudflare header
        $countryCode = $request->header('CF-IPCountry');

        // Check if the country code is empty or not defined
        if (empty($countryCode) || $countryCode === 'XX' || $countryCode === 'T1') {

            $countryCode = strtoupper($request->input('country', ''));

            if (empty($countryCode) || !isset(self::SUPPORTED_COUNTRIES[$countryCode])) {
                return self::DEFAULT_COUNTRY;
            }
        }

        $countryCode = strtoupper($countryCode);

        return self::SUPPORTED_COUNTRIES[$countryCode] ?? self::DEFAULT_COUNTRY;
    }

    private function clearBrandsCache()
    {
        // Clear all page caches
        for ($i = 1; $i <= 100; $i++) {
            $cacheKey = "brands_page_{$i}";
            Cache::forget($cacheKey);
        }
    }

    protected function clearCache()
    {
        Cache::flush();
    }

    /**
     * Display a paginated listing of brands with country-specific bonuses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Log headers for debugging
        //Log::info('All headers:', ['headers' => $request->headers->all()]);
        //Log::info('CF-IPCountry header:', ['value' => $request->header('CF-IPCountry')]);

        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 5)));

        // Get country code from Cloudflare header
        $countryCode = $request->header('CF-IPCountry');

        // Check if country is not determined or invalid
        if (empty($countryCode) || $countryCode === 'XX' || $countryCode === 'T1') {
            $defaultBrands = [
                [
                    'id' => 1,
                    'brand_name' => 'Default Casino 1',
                    'brand_image' => 'https://example.com/default1.jpg',
                    'rating' => 5,
                    'bonus' => self::DEFAULT_COUNTRY['bonus'],
                    'country' => self::DEFAULT_COUNTRY['name']
                ],
                [
                    'id' => 2,
                    'brand_name' => 'Default Casino 2',
                    'brand_image' => 'https://example.com/default2.jpg',
                    'rating' => 4,
                    'bonus' => self::DEFAULT_COUNTRY['bonus'],
                    'country' => self::DEFAULT_COUNTRY['name']
                ],
                [
                    'id' => 3,
                    'brand_name' => 'Default Casino 3',
                    'brand_image' => 'https://example.com/default3.jpg',
                    'rating' => 4,
                    'bonus' => self::DEFAULT_COUNTRY['bonus'],
                    'country' => self::DEFAULT_COUNTRY['name']
                ]
            ];

            //Paginator
            $total = count($defaultBrands);
            $items = array_slice($defaultBrands, ($page - 1) * $perPage, $perPage);

            return response()->json([
                'current_page' => $page,
                'data' => $items,
                'first_page_url' => url()->current() . '?page=1',
                'from' => ($page - 1) * $perPage + 1,
                'last_page' => ceil($total / $perPage),
                'last_page_url' => url()->current() . '?page=' . ceil($total / $perPage),
                'next_page_url' => $page < ceil($total / $perPage) ? url()->current() . '?page=' . ($page + 1) : null,
                'path' => url()->current(),
                'per_page' => $perPage,
                'prev_page_url' => $page > 1 ? url()->current() . '?page=' . ($page - 1) : null,
                'to' => min(($page - 1) * $perPage + $perPage, $total),
                'total' => $total,
            ]);
        }

        // Get country configuration for normal case
        $countryConfig = $this->getUserCountryConfig($request);

        // Get paginated brands
        $brands = Brand::orderBy('rating', 'desc')
            ->paginate($perPage);

        // Add country-specific bonus to each brand
        $brands->getCollection()->transform(function ($brand) use ($countryConfig) {
            $brand->bonus = $countryConfig['bonus'];
            $brand->country = $countryConfig['name'];
            return $brand;
        });

        return response()->json($brands);
    }

    /**
     * Store a newly created brand.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands',
            'brand_image' => 'nullable|url|max:1000',
            'rating' => 'required|integer|min:1|max:5'
        ]);

        try {
            $brand = Brand::create($data);
            $countryConfig = $this->getUserCountryConfig($request);
            $brand->bonus = $countryConfig['bonus'];
            $brand->country = $countryConfig['name'];

            return response()->json($brand, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error creating brand',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified brand.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $brand = Brand::findOrFail($id);
            $countryConfig = $this->getUserCountryConfig($request);
            $brand->bonus = $countryConfig['bonus'];
            $brand->country = $countryConfig['name'];

            return response()->json($brand);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Brand not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified brand.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $brand = Brand::findOrFail($id);

            $data = $request->validate([
                'brand_name' => 'required|string|max:255|unique:brands,brand_name,' . $id,
                'brand_image' => 'nullable|url|max:1000',
                'rating' => 'required|integer|min:1|max:5'
            ]);

            $brand->update($data);

            $countryConfig = $this->getUserCountryConfig($request);
            $brand->bonus = $countryConfig['bonus'];
            $brand->country = $countryConfig['name'];

            return response()->json($brand);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error updating brand',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Remove the specified brand.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $brand = Brand::findOrFail($id);
            $brand->delete();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error deleting brand',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
