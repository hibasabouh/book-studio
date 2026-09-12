<?php

namespace Database\Factories;

use App\Enums\ContactCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'category' => fake()->randomElement(ContactCategory::cases()),
            'message' => fake()->paragraph(),
            'read_at' => null,
        ];
    }

    /**
     * A message the admin has already opened.
     */
    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }

    public function ofCategory(ContactCategory $category): static
    {
        return $this->state(fn () => ['category' => $category]);
    }
}
