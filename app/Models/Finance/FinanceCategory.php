<?php

namespace App\Models\Finance;

use App\Concerns\LogsCrudActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class FinanceCategory extends Model
{
    use LogsCrudActivity;

    protected $fillable = [
        'name',
        'applies_to',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return Builder<static>
     */
    public function scopeForKind(Builder $query, string $kind): Builder
    {
        return $query->where(function (Builder $inner) use ($kind) {
            $inner->where('applies_to', $kind)->orWhere('applies_to', 'both');
        });
    }

    /**
     * @return list<array{id: int, name: string, applies_to: string}>
     */
    public static function options(?string $kind = null): array
    {
        return static::query()
            ->active()
            ->when($kind, fn (Builder $query) => $query->forKind($kind))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'applies_to'])
            ->map(fn (self $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'applies_to' => $category->applies_to,
            ])
            ->all();
    }
}
