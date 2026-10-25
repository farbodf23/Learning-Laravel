<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'review' => fake()->paragraph(),
            'rating' => fake()->numberBetween(1, 5),
        ];
    }
}
