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
use App\Notifications\OfficePendingApproval;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;


class OfficeControllerTest extends TestCase
{
use LazilyRefreshDatabase;
/**
 * A basic feature test example.
 */

/** @test */

public function itListsAllOfficesInPaginatedWay(): void
{
    Office::factory()->create([
        'approval_status'=> Office::APPROVAL_APPROVED,
    ]);
    $response = $this->get('/api/offices');



    $response->assertOk();
    $response->dump();
    $this->assertNotNull($response->json('data')[0]['id']);
}

/** @test */

public function itOnlyListsOfficesThatAreNotHiddenAndApproved(): void
{
    Office::factory(3)->create([
        'hidden'=> false,
        'approval_status'=> Office::APPROVAL_APPROVED
    ]);

    Office::factory(1)->create(['hidden'=> true]);
    Office::factory(1)->create(['approval_status'=> Office::APPROVAL_PENDING]);

    $response = $this->get('/api/offices');
    $response->assertOk();
    $response->dump();
    $response->assertJsonCount(3, 'data');

}

/** @test */

public function itListsOfficesThatIncludingHiddenAndUnApprovedIfFilteringForCurrentLoggedInUser(): void
{
    $user = User::factory()->create(['name' => 'mohamed']);

    Office::factory()->for($user)->create([
        'hidden'=> false,
        'approval_status'=> Office::APPROVAL_APPROVED
    ]);
    
    Office::factory()->for($user)->create([
        'hidden'=> true,
        'approval_status'=> Office::APPROVAL_PENDING
    ]);

    $this->actingAs($user);

    $response = $this->get('/api/offices?user_id='.$user->id);
    $response->assertOk();
    $response->dump();
    $response->assertJsonCount(2, 'data');

}

/** @test */

public function itFilterByUserId()
{
    $office1= Office::factory()->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

    $host = User::factory()->create(['name'=> 'mohamed']);
    $office2= Office::factory()->for($host)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

    $response = $this->get(
        '/api/offices?user_id='.$host->id
    );

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->dump();
}

/** @test */

public function itFilterByVisitor()
{
    Office::factory()->create(['approval_status'=> Office::APPROVAL_APPROVED,]);
    $user = User::factory()->create(['name'=> 'mohamed']);
    $office = Office::factory()->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

    Reservation::factory()->for($office)->for($user)->create([
    'start_date' => Carbon::parse('2023-11-01'),
    'end_date' => Carbon::parse('2023-11-05'),
    ]);

    $response = $this->get(
    '/api/offices?visitor_id='.$user->id
    );

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
}

/** @test */

public function itIncludeUserAndTagsAndImages()
{
    $user = User::factory()->create(['name'=> 'mohamed']);
    $tag = Tag::factory()->create(['name'=> 'mohamed']);
    $office= Office::factory()->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

    $office->tags()->attach($tag);
    $office->images()->create(['path' => 'image.jpg']);

    $response = $this->get('/api/offices');

    $response->assertOk();
    $response->dump();


    $response->assertJsonCount(1, 'data');
}

/** @test */

public function itReturnTheNumberOfActiveReservation(): void
{

    $office= Office::factory()->create(['approval_status'=> Office::APPROVAL_APPROVED,]);
    Reservation::factory()->for($office)->create([
        'status'=> Reservation::STATUS_ACTIVE,
        'start_date' => Carbon::parse('2023-11-01'),
        'end_date' => Carbon::parse('2023-11-05'),

    ]);
    Reservation::factory()->for($office)->create([
        'status'=> Reservation::STATUS_CANCELED,
        'start_date' => Carbon::parse('2023-11-01'),
        'end_date' => Carbon::parse('2023-11-05'),

    ]);


    $response = $this->get('/api/offices');

    $response->assertOk();
    $response->dump();
    $response->assertJsonCount(1, 'data');
}

/** @test */

public function itShowTheOffice(): void
{

    $office= Office::factory()->create(['approval_status'=> Office::APPROVAL_APPROVED,]);
    $response = $this->get('/api/offices/'.$office->id);

    $response->assertOk();
    $response->dump();
}

/** @test */

public function itCreatesAnOffice(): void
{
    Notification::fake();
    $admin = User::factory()->create(['is_admin'=> true]);
    $user = User::factory()->create();
    $tag1 = Tag::factory()->create(['name'=> 'mohamed']);
    $tag2 = Tag::factory()->create(['name'=> 'ahmed']);

    $this->actingAs($user);

    $response = $this->postJson('/api/offices', [
    'title' => 'Al mahahla Office',
    'description' => 'description',
    'lat' => '39.544121484554',
    'lng' => '-8.35453121215',
    'address_line1' => 'address',
    'price_per_day' => 10000,
    'monthly_discount' => 5,
    'tags' => [$tag1->id]
    ]);

    $response->assertCreated('data.title','Al mahahla Office');
    $response->assertjsonCount(1,'data.tags');
    $response->assertjsonpath('data.user.id',$user->id);
    $this->assertDatabaseHas('offices_tags', [
    'office_id' => $response->json('data.id'),
    'tag_id' => $tag1->id,
    ]);
    $response->assertJsonStructure(['data' => ['id', 'title', 'description', 'tags' => []]]);
    Notification::assertSentTO($admin,OfficePendingApproval::class);

}

/** @test */


public function itDoesntAllowCreatingIfScopeNotProvided(): void
{
    $user = User::factory()->create();
    $token= $user->createToken('test' , []);

    $response = $this->postJson('/api/offices', [] , [
        'Authorization' => 'Bearer '.$token->plainTextToken
    ]);

    $response->assertStatus(403);

}

/** @test */

public function itupdatesAnOffice(): void
{
    $user = User::factory()->create(['name'=> 'ahmed']);
    $tag = Tag::factory()->create(['name'=> 'mohamed']);
    $tag2 = Tag::factory()->create(['name'=> 'ahmed']);

    $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

    $office->tags()->attach($tag);


    $this->actingAs($user);

    $response = $this->putJson('/api/offices/'.$office->id, [
        'title' => 'Amazing Office',
        'tags' => [$tag->id , $tag2->id],
    ]);
    

    $response->dump();
    $response->assertJsonStructure(['data' => ['tags']]);
    $response->assertJsonCount(2,'data.tags');
    $response->assertJsonPath('data.tags.0.id',$tag->id );
    $response->assertJsonPath('data.tags.1.id',$tag2->id );
    $response->assertOk()
    ->assertJsonPath('data.title','Amazing Office');

}

/** @test */

public function itDoesntUpdatesAnOfficeThatDoesntBelongToUser(): void
{
    $user = User::factory()->create(['name'=> 'Mooo']);
    $anotherUser =User::factory()->create(['name'=> 'Khaled']);
    $office = Office::factory()->for($anotherUser)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);


    $this->actingAs($user);

    $response = $this->putJson('/api/offices/'.$office->id, [
        'title' => 'Amazing Office',
    ]);
    
    $response->assertStatus(403);

}

/** @test */

public function itMarksTheOfficeApprovalAsPendingIfDirty(): void
{
    Notification::fake();
    $admin = User::factory()->create(['is_admin'=> true]);
    $user = User::factory()->create(['name'=> 'Mooo']);
    $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);


    $this->actingAs($user);

    $response = $this->putJson('/api/offices/'.$office->id, [
        'lat' => '35.5465454984',
    ]);

    $response->dump();
    $response->assertOk();
    $this->assertDatabaseHas('offices',[
        'id' => $office->id,
        'approval_status'=> Office::APPROVAL_PENDING,
    ]);
    Notification::assertSentTO($admin,OfficePendingApproval::class);
}


/** @test */

public function itupdatesFeatuerdPhotoForTheOffice(): void
{
    $user = User::factory()->create(['name'=> 'ahmed']);
    $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

    $image = $office->images()->create([
        'path' => 'image.jpg'
    ]);


    $this->actingAs($user);

    $response = $this->putJson('/api/offices/'.$office->id, [
        'featured_image_id'=> $image->id
    ]);
    


     $response->assertOk()
     ->assertJsonPath('data.featured_image_id',$image->id);

}

/** @test */

public function itDoesntupdatesFeatuerdPhotoThatBelongsToAnotherOffice(): void
{
    $user = User::factory()->create(['name'=> 'ahmed']);
    $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);
    $office2 = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

    $image = $office2->images()->create([
        'path' => 'image.jpg'
    ]);

    $this->actingAs($user);

    $response = $this->putJson('/api/offices/'.$office->id, [
        'featured_image_id'=> $image->id
    ]);
    
     $response->assertUnprocessable();
}

/** @test */

public function ItCanDeleteOffice(): void
{

    $user = User::factory()->create(['name'=> 'Mooo']);
    $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

    $image = $office->images()->create([
        'path'=> 'office_image.jpg'
    ]);

    $this->actingAs($user);

    $response = $this->delete('/api/offices/'.$office->id);

    $response->assertOk();
    $this->assertSoftDeleted($office);

    $this->assertModelMissing($image);
    Storage::disk('public')->assertMissing('office_image.jpg');

}

/** @test */

public function ItCantDeleteAnOfficeThatHasReservations(): void
{

    $user = User::factory()->create(['name'=> 'Mooo']);
    $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);
    Reservation::factory()->for($office)->for($user)->create([
        'start_date' => Carbon::parse('2023-11-01'),
        'end_date' => Carbon::parse('2023-11-05'),
        ]);
    
    $this->actingAs($user);

    $response = $this->deleteJson('/api/offices/'.$office->id);

    $response->assertUnprocessable();
    $this->assertNotSoftDeleted($office);
    $this->assertDatabaseHas('offices' , [
        'id'=> $office->id,
        'deleted_at' => null,
    ]);

}
}
