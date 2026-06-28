<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Chapter>
 */
class ChapterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'order' => 0,
        ];
    }
}
