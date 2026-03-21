<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\CategoryType;
use App\Enums\HouseholdMemberRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class CategoryService
{
    /**
     * Get the user's categories grouped by type.
     * Calculates total spend per category.
     *
     * @return \Illuminate\Support\Collection<(int|string), Collection<int, Category>>
     */
    public function listCategories(User $user, ?string $type = null): \Illuminate\Support\Collection
    {
        $household = $user->household;

        if (! $household) {
            return collect();
        }

        /** @var Builder<Category> $query */
        $query = $household->categories()->with('user');

        if ($type) {
            $query->where('type', $type);
        }

        /** @var Collection<int, Category> $categories */
        $categories = $query->get();

        if ($categories->isEmpty()) {
            return collect();
        }

        $spends = DB::table('transactions')
            ->whereIn('category_id', $categories->pluck('id'))
            ->select('category_id', DB::raw('SUM(amount) as total_spend'))
            ->groupBy('category_id')
            ->get()
            ->keyBy('category_id');

        /** @var Category $category */
        foreach ($categories as $category) {
            $spendRecord = $spends->get($category->id);
            $total = $spendRecord ? (float) $spendRecord->total_spend : 0.0;
            $category->setAttribute('total_spend', $total);
        }

        return $categories->groupBy(fn (Category $cat) => (string) $cat->type->value);
    }

    /**
     * Create a new category for the household.
     *
     * @param  array<string, mixed>  $data
     */
    public function createCategory(User $user, array $data): Category
    {
        $household = $user->household;

        if (! $household) {
            throw new NotFoundHttpException('User does not belong to a household.');
        }

        // The unique constraint is handled at the database level but we can catch it or pre-validate
        $exists = $household->categories()
            ->where('name', $data['name'])
            ->where('type', $data['type'])
            ->exists();

        if ($exists) {
            throw new ConflictHttpException('A category with this name and type already exists in your household.');
        }

        /** @var Category $category */
        $category = $household->categories()->create([
            ...$data,
            'user_id' => $user->id,
        ]);

        return $category;
    }

    /**
     * Update an existing category.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(User $user, Category $category, array $data): Category
    {
        $this->ensureCanManageCategory($user, $category);

        if (isset($data['type'])) {
            $typeValue = $data['type'] instanceof CategoryType ? $data['type']->value : $data['type'];
            if ($typeValue !== $category->type->value) {
                $this->ensureNoTransactions($category);
            }
        }

        if (isset($data['name']) || isset($data['type'])) {
            $nameToCheck = $data['name'] ?? $category->name;
            $typeValue = $data['type'] instanceof CategoryType ? $data['type']->value : $data['type'];
            $typeToCheck = $typeValue ?? $category->type->value;

            $exists = $category->household->categories()
                ->where('name', $nameToCheck)
                ->where('type', $typeToCheck)
                ->where('id', '!=', $category->id)
                ->exists();

            if ($exists) {
                throw new ConflictHttpException('A category with this name and type already exists in your household.');
            }
        }

        $category->update($data);

        return $category;
    }

    /**
     * Delete an existing category.
     */
    public function deleteCategory(User $user, Category $category): void
    {
        $this->ensureCanManageCategory($user, $category);
        $this->ensureNoTransactions($category);

        $category->delete();
    }

    /**
     * Ensure the user can manage the category.
     * Must be Owner/Member of the Household, OR the user who created the category.
     */
    private function ensureCanManageCategory(User $user, Category $category): void
    {
        if ($category->user_id === $user->id) {
            return; // Creator can always manage
        }

        // User is not creator, are they an Owner or Member?
        $member = $category->household->members()->where('user_id', $user->id)->first();
        if (! $member || $member->role === HouseholdMemberRole::Viewer) {
            throw new AccessDeniedHttpException('You do not have permission to manage this category.');
        }
    }

    /**
     * Ensure the category has no transactions attached.
     */
    private function ensureNoTransactions(Category $category): void
    {
        if ($category->transactions()->exists()) {
            throw new ConflictHttpException('Cannot modify or delete a category that has associated transactions.');
        }
    }
}
