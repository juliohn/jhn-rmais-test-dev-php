<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
use App\Models\Supplier;
use DateTime;
use App\Http\Requests\SupplierRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;


class SupplierController extends Controller
{
    private const ALLOWED_SORT_FIELDS = ['name', 'id', 'email'];
    private const ALLOWED_SORT_DIRECTIONS = ['asc', 'desc'];

    private function sendResponse($success = true, $message = '', $data = null, $code = 200)
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    private function getCacheKey(Request $request): string
    {
        // Add timestamp to cache key to invalidate all supplier listings when needed
        $timestamp = Cache::get('suppliers_cache_timestamp', now()->timestamp);
        
        return 'suppliers_' . $timestamp . '_' . 
            $request->input('sort_by', 'id') . '_' . 
            $request->input('sort_direction', 'asc') . '_' . 
            $request->input('search', '') . '_' . 
            $request->input('page', 1);
    }

    private function clearSupplierCache(string $id): void
    {
        // Remove specific supplier cache
        Cache::forget('supplier_' . $id);
        
        // Clear all paginated results by using a timestamp key
        Cache::put('suppliers_cache_timestamp', now()->timestamp);
    }

    private function getSortParameters(Request $request): array
    {
        $sortBy = in_array($request->input('sort_by'), self::ALLOWED_SORT_FIELDS) 
            ? $request->input('sort_by') 
            : 'id';
        
        $sortDirection = in_array(strtolower($request->input('sort_direction')), self::ALLOWED_SORT_DIRECTIONS) 
            ? strtolower($request->input('sort_direction')) 
            : 'asc';

        return [$sortBy, $sortDirection];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Criar uma chave única para o cache baseada nos parâmetros da request
            $cacheKey = $this->getCacheKey($request);

            // Recuperar do cache ou executar a query
            $suppliers = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($request) {
                [$sortBy, $sortDirection] = $this->getSortParameters($request);
                $search = $request->input('search');
                
                return Supplier::with([
                    'phones' => function($query) {
                        $query->where('is_main', true);
                    },
                    'addresses' => function($query) {
                        $query->where('is_main', true);
                    }
                ])
                ->when($search, function($query) use ($search) {
                    $query->where(function($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%")
                          ->orWhere('document', 'like', "%{$search}%");
                    });
                })
                ->orderBy($sortBy, $sortDirection)
                ->paginate(10)
                ->through(function ($supplier) {
                    $supplier->phone = $supplier->phones->first()?->number;
                    $supplier->main_address = $supplier->addresses->first();
                    unset($supplier->phones, $supplier->addresses);
                    return $supplier;
                });
            });
            
            return $this->sendResponse(true, 'Suppliers retrieved successfully', $suppliers);
        } catch (\Exception $exception) {
            return $this->sendResponse(false, $exception->getMessage(), null, 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierRequest $request) 
    {
        try {
            $supplier = Supplier::create([
                'name' => $request->name,
                'document' => $request->document,
                'document_type' => $request->document_type,
                'email' => $request->email
            ]);

            // Create phone relationships
            foreach ($request->phones as $phone) {
                
                $supplier->phones()->create([
                    'number' => $phone['number'],
                    'is_main' => $phone['is_main']
                ]);
            }

            // Create address relationship using request->address
            $supplier->addresses()->create([
                'is_main' => 1,
                'street' => $request->address['street'],
                'number' => $request->address['number'],
                'complement' => $request->address['complement'],
                'neighborhood' => $request->address['neighborhood'],
                'city' => $request->address['city'],
                'state' => $request->address['state'],
                'cep' => $request->address['cep']
            ]);

            // Update cache timestamp instead of using tags
            Cache::put('suppliers_cache_timestamp', now()->timestamp);
            
            return $this->sendResponse(true, 'Supplier registered successfully', $supplier);
        } catch (\Exception $exception) {
            return $this->sendResponse(false, $exception->getMessage(), null, 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $supplier = Cache::remember('supplier_' . $id, now()->addMinutes(10), function () use ($id) {
                $supplier = Supplier::with([
                    'phones' => function($query) {
                        $query->orderByDesc('is_main');
                    },
                    'addresses' => function($query) {
                        $query->where('is_main', true);
                    }
                ])->findOrFail($id);
                
                $supplier->address = $supplier->addresses->first();
                unset($supplier->addresses);
                
                return $supplier;
            });
            
            return $this->sendResponse(true, 'Supplier retrieved successfully', $supplier);
        } catch (\Exception $exception) {
            return $this->sendResponse(false, $exception->getMessage(), null, 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, string $id): \Illuminate\Http\JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);
            
            $supplier->update([
                'name' => $request->name,
                'document' => $request->document,
                'document_type' => $request->document_type,
                'email' => $request->email
            ]);

            // Delete existing phones and create new ones
            $supplier->phones()->delete();
            foreach ($request->phones as $phone) {
                $supplier->phones()->create([
                    'number' => $phone['number'],
                    'is_main' => $phone['is_main']
                ]);
            }

            // Update main address
            $supplier->addresses()->where('is_main', 1)->update([
                'street' => $request->address['street'],
                'number' => $request->address['number'],
                'complement' => $request->address['complement'],
                'neighborhood' => $request->address['neighborhood'],
                'city' => $request->address['city'],
                'state' => $request->address['state'],
                'cep' => $request->address['cep']
            ]);

            // Limpar caches específicos
            $this->clearSupplierCache($id);
            
            return $this->sendResponse(true, 'Supplier updated successfully', $supplier);
        } catch (\Exception $exception) {
            return $this->sendResponse(false, $exception->getMessage(), null, 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): \Illuminate\Http\JsonResponse
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            // Update cache timestamp instead of using tags
            Cache::forget('supplier_' . $id);
            Cache::put('suppliers_cache_timestamp', now()->timestamp);
            
            return $this->sendResponse(true, 'Supplier deleted successfully');
        } catch (\Exception $exception) {
            return $this->sendResponse(false, $exception->getMessage(), null, 500);
        }
    }
}
