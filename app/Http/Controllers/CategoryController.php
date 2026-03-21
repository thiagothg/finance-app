<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CategoryController extends Controller
{
    public function __construct(
        private CategoryService $categoryService
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return array<string, mixed>
     */
    public function index(Request $request)
    {
        $type = $request->query('type');
        $groupedCategories = $this->categoryService->listCategories(
            $request->user(),
            is_string($type) ? $type : null
        );

        $response = [];
        $totalCount = 0;
        $totalBy = [];

        foreach ($groupedCategories as $key => $collection) {
            $totalCount += $collection->count();
            $response[$key] = CategoryResource::collection($collection);
            $totalBy[$key] = [
                'total_spent' => $collection->sum('total_spend'),
                'count' => $collection->count(),
                'total_budget' => $collection->sum('budget'),
                'remaining_budget' => $collection->sum('budget') - $collection->sum('total_spend'),
            ];
        }

        return [
            'data' => $response,
            'meta' => [
                'total_count' => $totalCount,
                'total_by_type' => $totalBy,
            ],
        ];
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->createCategory(
            $request->user(),
            $request->validated()
        );

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $updatedCategory = $this->categoryService->updateCategory(
            $request->user(),
            $category,
            $request->validated()
        );

        return new CategoryResource($updatedCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category): Response
    {
        $this->categoryService->deleteCategory($request->user(), $category);

        return response()->noContent();
    }
}
