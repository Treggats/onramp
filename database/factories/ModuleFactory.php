<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slug' => function ($module) {
                return Str::slug($module['name']);
            },
            'is_bonus' => $this->faker->boolean(20),
        ];
    }
}
