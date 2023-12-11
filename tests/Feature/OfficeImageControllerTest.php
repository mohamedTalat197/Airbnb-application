<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\Office;
use App\Models\User;



class OfficeImageControllerTest extends TestCase
{
use LazilyRefreshDatabase;

/** @test */
    public function ItsUploadImageUnderTheOffice(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['name'=>'mohamed']);
        $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

        $this->actingAs($user);

        $response = $this->post('/api/offices/'.$office->id.'/images',[
            'image' => UploadedFile::fake()->image('image.png')
        ]);

        $response->assertCreated();
        Storage::disk('public')->assertExists(
            $response->json('data.path')
        );
        $response->dump();
    }

    /** @test */

    public function ItsDeleteOfficeImage(): void
    {
        Storage::disk('public')->put('/office_image.jpg', 'empty');

        $user = User::factory()->create(['name'=>'mohamed']);
        $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

        $image = $office->images()->create([
            'path'=> 'office_image.jpg'
        ]);

        $image2 = $office->images()->create([
            'path'=> 'image.jpg'
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson('/api/offices/'.$office->id.'/images/'.$image->id,[
            
        ]);

        $response->assertOk();
        $this->assertModelMissing($image);
        Storage::disk('public')->assertMissing('office_image.jpg');


    }

    /** @test */

    public function ItsDoesntDeleteTheOnlyOfficeImage(): void
    {
        $user = User::factory()->create(['name'=>'mohamed']);
        $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);
        
        $image = $office->images()->create([
            'path'=> 'office_image.jpg'
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson('/api/offices/'.$office->id.'/images/'.$image->id,[
            
        ]);

        $response->assertUnprocessable();

    }

    /** @test */

    public function ItsDoesntDeleteTheImageThatBelongsToAnotherOffice(): void
    {
        $user = User::factory()->create(['name'=>'mohamed']);
        $office = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);
        $office2 = Office::factory()->for($user)->create(['approval_status'=> Office::APPROVAL_APPROVED,]);

        
        $image = $office->images()->create([
            'path'=> 'office_image.jpg'
        ]);

        $image2 = $office2->images()->create([
            'path'=> 'office_image.jpg'
        ]);

        $this->actingAs($user);

        $response = $this->deleteJson('/api/offices/'.$office2->id.'/images/'.$image->id,[
            
        ]);

        $response->assertNotFound();

        // here using keyed implicit binding , that mean to attach the id from the parent in relation...

    }

    /** @test */

    public function ItsDoesntDeleteTheFeaturedOfficeImage(): void
    {
        $user = User::factory()->create(['name'=>'mohamed']);
        $office = Office::factory()->for($user)->create([
            'approval_status'=> Office::APPROVAL_APPROVED,
        ]);
        
        $image = $office->images()->create([
            'path'=> 'image.jpg'
        ]);

        $image2 = $office->images()->create([ 
            'path'=> 'office_image.jpg'
        ]);

        $office->update(['featured_image_id' => $image2->id]);

        $this->actingAs($user);

        $response = $this->deleteJson('/api/offices/'.$office->id.'/images/'.$image2->id,[
            
        ]);

        $response->assertUnprocessable();

    }


}
