<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAddressRequest;
use App\Http\Requests\Api\V1\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:addresses.view')->only(['index', 'show']);
        $this->middleware('permission:addresses.create')->only('store');
        $this->middleware('permission:addresses.update')->only('update');
        $this->middleware('permission:addresses.delete')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->latest()
            ->paginate();

        return ApiResponse::collection(AddressResource::collection($addresses));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAddressRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $this->applyDefaultFlags($user->id, $data);

        $address = $user->addresses()->create($data);

        $this->ensureSingleDefaults($user->id);

        return ApiResponse::resource(new AddressResource($address), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Address $address): JsonResponse
    {
        $address = $this->ensureOwnership($request, $address);

        return ApiResponse::resource(new AddressResource($address));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAddressRequest $request, Address $address): JsonResponse
    {
        $address = $this->ensureOwnership($request, $address);

        $data = $request->validated();

        $this->applyDefaultFlags($request->user()->id, $data, $address->id);

        $address->update($data);

        $this->ensureSingleDefaults($request->user()->id);

        return ApiResponse::resource(new AddressResource($address));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Address $address): JsonResponse
    {
        $address = $this->ensureOwnership($request, $address);

        $address->delete();

        $this->ensureSingleDefaults($request->user()->id);

        return ApiResponse::message('Address deleted.');
    }

    private function ensureOwnership(Request $request, Address $address): Address
    {
        if ($address->user_id !== $request->user()->id) {
            abort(404);
        }

        return $address;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyDefaultFlags(int $userId, array $data, ?int $ignoreId = null): void
    {
        if (! empty($data['default_shipping'])) {
            $query = Address::query()->where('user_id', $userId);

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            $query->update(['default_shipping' => false]);
        }

        if (! empty($data['default_billing'])) {
            $query = Address::query()->where('user_id', $userId);

            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            $query->update(['default_billing' => false]);
        }
    }

    private function ensureSingleDefaults(int $userId): void
    {
        $this->ensureSingleDefault($userId, 'default_shipping');
        $this->ensureSingleDefault($userId, 'default_billing');
    }

    private function ensureSingleDefault(int $userId, string $column): void
    {
        $query = Address::query()->where('user_id', $userId);
        $count = (clone $query)->count();

        if ($count === 0) {
            return;
        }

        $defaultCount = (clone $query)->where($column, true)->count();

        if ($defaultCount === 1) {
            return;
        }

        (clone $query)->update([$column => false]);

        $latest = (clone $query)->latest('id')->first();

        if ($latest) {
            $latest->update([$column => true]);
        }
    }
}
