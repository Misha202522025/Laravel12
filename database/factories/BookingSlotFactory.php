<?php

namespace Database\Factories;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingSlot>
 */
class BookingSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'start_time' => $this->faker->dateTimeBetween('+1 day', '+1 week'),
            'end_time' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
        ];
    }
}
