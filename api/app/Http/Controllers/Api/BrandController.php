<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    /**
     * List of supported country codes and their configurations
     */
    private const SUPPORTED_COUNTRIES = [
        'FR' => [
            'name' => 'France',
            'bonus' => '200% up to €500 + 500 Free Spins'
        ],
        'CM' => [
            'name' => 'Cameroon',
            'bonus' => 'Up to €1000 + 365 Free Spins'
        ],
        'EN' => [
            'name' => 'England',
            'bonus' => '100% up to €500 + 20 Free Spins'
        ]
    ];

    /**
     * Default country configuration when geolocation fails
     */
    private const DEFAULT_COUNTRY = 'INT';

    protected $defaultCountry = 'XX';
    protected $cacheExpiration = 3600;

    /**
     * Get user's country from Cloudflare header or return default
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function getUserCountry(Request $request): string
    {
        $country = strtoupper($request->header('CF-IPCountry', ''));
        if (empty($country)) {
            $country = strtoupper($request->header('cf-ipcountry', ''));
        }
        //dd($country);
        if (empty($country)) {
            $country = strtoupper($request->input('country', self::DEFAULT_COUNTRY));
        }

        // If the country is not supported, use the default value
        if (!array_key_exists($country, self::SUPPORTED_COUNTRIES) && $country !== self::DEFAULT_COUNTRY) {
            $country = self::DEFAULT_COUNTRY;
        }

        return $country;
    }

    private function clearBrandsCache()
    {
        // Clear all page caches
        for ($i = 1; $i <= 100; $i++) {
            $cacheKey = "brands_page_{$i}";
            Cache::forget($cacheKey);
        }
    }

    protected function getCountryCode(Request $request)
    {
        return strtoupper($request->header('CF-IPCountry', $this->defaultCountry));
    }

    protected function clearCache()
    {
        Cache::flush();
    }

    /**
     * Display a paginated listing of brands.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 5)));
        $country = $this->getUserCountry($request);

        return Brand::when($country !== self::DEFAULT_COUNTRY, function($query) use ($country) {
                    return $query->where('country_code', $country);
                })
                ->orderBy('rating', 'desc')
                ->paginate($perPage);
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

        $data['country_code'] = $this->getCountryCode($request);

        try {
            $brand = Brand::create($data);
            $this->clearCache();

            return response()->json($brand, 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified brand.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $brand = Brand::findOrFail($id);
            return response()->json($brand);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Casino non trouvé',
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
            $this->clearCache();

            return response()->json($brand);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    /**
     * Remove the specified brand.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $brand = Brand::findOrFail($id);
            $brand->delete();
            $this->clearCache();

            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
