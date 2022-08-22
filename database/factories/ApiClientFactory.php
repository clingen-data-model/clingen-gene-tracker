<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ApiClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'uuid' => $this->faker()->uuid(),
            'name' => $this->faker()->unique()->name(),
            'contact_email' => $this->faker()->email(),
        ];
    }
}
