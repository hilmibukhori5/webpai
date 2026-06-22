<?php

namespace Database\Factories;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'no_induk' => $this->faker->unique()->numerify('19502010####'),
            'nama' => $this->faker->name(),
            'prodi' => 'S1 Ilmu Aktuaria',
        ];
    }
}
