<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Office;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{

    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'=> User::factory(),
            'office_id'=> Office::factory(),
            'price'=> $this->faker->numberBetween(10000 , 20000),
            'status'=> Reservation::STATUS_ACTIVE,
            'start_date'=>now()->addDay(1)->format('Y-M-D'),
            'end_date'=>now()->addDay(5)->format('Y-M-D'),
        ];
    }
}
