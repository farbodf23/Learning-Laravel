<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Review;
class Book extends Model
{
    use HasFactory;

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where('title', 'LIKE', '%'.$search.'%');
    }

    public function scopePopular(
        Builder $query,
        string $from,
        string $to
    ): void {
        $query->withCount([
            'reviews' => function (Builder $reviewQuery) use ($from, $to) {
                $this->dateRangeFilter($reviewQuery, $from, $to);
            },
        ])->orderBy('reviews_count', 'desc');
    }

    public function scopeHighestRated(Builder $query): void
    {
        $query->withAvg('reviews', 'rating')
            ->orderBy('reviews_avg_rating', 'desc');
    }

    private function dateRangeFilter(
        Builder $query,
        string $from,
        string $to
    ): void {
        $query->whereBetween('created_at', [$from, $to]);
    }
}
