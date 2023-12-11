<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use App\Models\User;
use DB;

class UserReservationControllerTest extends TestCase
{

    use LazilyRefreshDatabase;

    /** @test */
    public function itListReservationThatBelongToUser(): void
    {
        $user = User::factory()->create(['name'=>'mohamed']);

        $reservation = Reservation::factory()->for($user)->create([
            'start_date' => Carbon::parse('2023-11-01'),
            'end_date' => Carbon::parse('2023-11-05'),
            ]);

        $image = $reservation->office->images()->create([
            'path'=> 'office_image.jpg'
        ]);

        $reservation->office()->update(['featured_image_id' => $image->id]);

        $reservation2 = Reservation::factory()->create([
            'start_date' => Carbon::parse('2023-11-01'),
            'end_date' => Carbon::parse('2023-11-05'),
            ]);    

        $this->actingAs($user);

        $response = $this->get('/api/reservation');

        $response->assertJsonStructure(['data','links','meta']);
        $response->assertJsonCount(1,'data');
        $response->assertJsonStructure(['data'=> ['*' =>['id' , 'office']]]);
        $response->assertJsonPath('data.0.office.featured_image_id', $image->id);

    }

    /** @test */
    public function itListReservationFilterByRange(): void
    {
        $user = User::factory()->create(['name'=>'mohamed']);

        $fromDate = '2024-1-10';
        $toDate = '2024-2-10';

        // within the Range.. and These Should retun..
        $reservation1 = Reservation::factory()->for($user)->create([
            'start_date' => Carbon::parse('2024-1-9'),
            'end_date' => Carbon::parse('2024-2-9'),
            ]);

        $reservation2 = Reservation::factory()->for($user)->create([
            'start_date' => Carbon::parse('2024-1-11'),
            'end_date' => Carbon::parse('2024-2-11'),
            ]); 

        $reservation3 = Reservation::factory()->for($user)->create([
            'start_date' => Carbon::parse('2024-1-20'),
            'end_date' => Carbon::parse('2024-2-20'),
        ]);   

        // within The Range but belongs to Different User..
        $reservationX = Reservation::factory()->create([
            'start_date' => Carbon::parse('2024-1-11'),
            'end_date' => Carbon::parse('2024-2-9'),
        ]); 

        // Out of range..
        $reservation4 = Reservation::factory()->for($user)->create([
            'start_date' => Carbon::parse('2023-12-17'),
            'end_date' => Carbon::parse('2023-12-25'),
        ]);    

        $reservation5 = Reservation::factory()->for($user)->create([
            'start_date' => Carbon::parse('2024-3-1'),
            'end_date' => Carbon::parse('2024-3-2'),
        ]);         

        $this->actingAs($user);

        $response = $this->getJson('/api/reservation?'.http_build_query([
            'from_date' => $fromDate , 
            'to_date' => $toDate 
        ]));

        $response->assertJsonCount(3,'data');

        $this->assertEquals(
            [$reservation1->id, $reservation2->id, $reservation3->id],
            collect($response->json('data'))->pluck('id')->toArray()
        );
    }

    /** @test */
    public function itListReservationFilterByStatus(): void
    {
        $user = User::factory()->create(['name'=>'mohamed']);

        $reservation1 = Reservation::factory()->for($user)->create([
            'start_date' => Carbon::parse('2024-1-9'),
            'end_date' => Carbon::parse('2024-2-9'),
            'status' => Reservation::STATUS_ACTIVE
            ]);

        $reservation2 = Reservation::factory()->for($user)->create([
            'start_date' => Carbon::parse('2024-1-11'),
            'end_date' => Carbon::parse('2024-2-11'),
            'status' => Reservation::STATUS_CANCELED
            ]); 

        $this->actingAs($user);

        $response = $this->getJson('/api/reservation?'.http_build_query([
            'status' => Reservation::STATUS_ACTIVE
        ]));

        $response
        ->assertJsonCount(1,'data')
        ->assertJsonPath('data.0.id',$reservation1->id);


    }    

    /** @test */
    public function itListReservationFilterByOfficeID(): void
    {
        $user = User::factory()->create(['name'=>'mohamed']);
        $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

        $reservation1 = Reservation::factory()->for($user)->for($office)->create([
            'start_date' => Carbon::parse('2024-1-9'),
            'end_date' => Carbon::parse('2024-2-9'),
            'status' => Reservation::STATUS_ACTIVE
            ]);

        $reservation2 = Reservation::factory()->for($user)->create([
            'start_date' => Carbon::parse('2024-1-11'),
            'end_date' => Carbon::parse('2024-2-11'),
            'status' => Reservation::STATUS_CANCELED
            ]); 

        $this->actingAs($user);

        $response = $this->getJson('/api/reservation?'.http_build_query([
            'office_id' => $office->id
        ]));

        $response
        ->assertJsonCount(1,'data')
        ->assertJsonPath('data.0.id',$reservation1->id);


    }   

    /** @test */
    public function itCreateReservation(): void
    {
        $user = User::factory()->create(['name'=>'mohamed']);
        $office = Office::factory()->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

        $this->actingAs($user);

        $response = $this->postJson('/api/reservation' , [
            'office_id' => $office->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(41),
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.status', Reservation::STATUS_ACTIVE);
        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.office_id', $office->id);

    }   

      
}
