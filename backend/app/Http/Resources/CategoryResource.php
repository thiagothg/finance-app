<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\CategoryType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property int $id
 * @property string $name
 * @property CategoryType $type
 * @property string|null $icon
 * @property string|null $color
 * @property float|null $budget
 * @property int|null $user_id
 * @property float|null $total_spend
 */
class CategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'icon' => $this->icon,
            'color' => $this->color,
            'budget' => $this->budget ?? 0.0,
            'user_id' => $this->user_id,
            'total_spend' => $this->total_spend ?? 0.0,
        ];
    }
}
