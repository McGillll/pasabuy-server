<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandStoreRequest;
use App\Http\Requests\BrandUpdateRequest;
use App\Http\Resources\BrandResource;
use App\Interfaces\Brand\BrandServiceInterface;
use App\Traits\ApiResponse;
use App\Traits\InteractsWithCache;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    use ApiResponse, InteractsWithCache;

    public function __construct(
        protected BrandServiceInterface $brandService,
    ) {}

    public function index(Request $request)
    {
        $payload = (object) $request->query();
        $etag = $this->etagFor('brands', $payload);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $brands = $this->brandService->all($payload);

        return $this->success([
            'items' => BrandResource::collection($brands->items()),
            'current_page' => $brands->currentPage(),
            'last_page' => $brands->lastPage(),
            'total' => $brands->total(),
        ])->header('ETag', $etag);
    }

    public function show(Request $request, string $uuid)
    {
        $etag = $this->etagFor('brands', (object) ['uuid' => $uuid]);

        if ($notModified = $this->notModified($request, $etag)) {
            return $notModified;
        }

        $brand = $this->brandService->findByUuid($uuid);

        return $this->success(new BrandResource($brand))->header('ETag', $etag);
    }

    public function store(BrandStoreRequest $request)
    {
        $payload = (object) $request->validated();

        $brand = $this->brandService->create($payload);

        return $this->success(new BrandResource($brand), 'Brand created successfully', 201);
    }

    public function update(BrandUpdateRequest $request, string $uuid)
    {
        $payload = (object) $request->validated();

        $brand = $this->brandService->update($uuid, $payload);

        return $this->success(new BrandResource($brand), 'Brand updated successfully');
    }

    public function destroy(string $uuid)
    {
        $this->brandService->delete($uuid);

        return $this->success(null, 'Brand deleted successfully');
    }
}
