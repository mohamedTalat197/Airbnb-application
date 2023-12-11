<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Office;
use App\Models\User;



/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Office>
 */
class OfficeFactory extends Factory
{

    protected $model = Office::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'=> User::factory(),
            'title'=> $this->faker->sentence,
            'description'=> $this->faker->paragraph,
            'lat'=> $this->faker->latitude,
            'lng'=> $this->faker->longitude,
            'address_line1'=> $this->faker->address,
            'approval_status'=> Office::APPROVAL_PENDING,
            'hidden'=> false,
            'price_per_day'=> $this->faker->numberBetween(1000 , 2000),
            'monthly_discount'=> 0,
        ];
    }

    public function pending()
    {
        return $this->state([
            'approval_status'=> Office::APPROVAL_PENDING,
        ]);
    }

    public function approved()
    {
        return $this->state([
            'approval_status'=> Office::APPROVAL_APPROVED,
        ]);
    }

    public function hidden()
    {
        return $this->state([
            'hidden'=> true,
        ]);
    }

    
}
