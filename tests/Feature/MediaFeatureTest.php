<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        return User::factory()->create([
            'role_id' => Role::where('slug', 'owner')->first()->id,
        ]);
    }

    public function test_media_library_index_can_be_accessed(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner)->get('/admin/media');

        $response->assertOk();
    }

    public function test_file_can_be_uploaded_to_media_library(): void
    {
        Storage::fake('public');
        $owner = $this->owner();

        $file = UploadedFile::fake()->image('solar-panel.jpg', 600, 400);

        $response = $this->actingAs($owner)
            ->post('/admin/media', [
                'file' => $file,
                'category' => 'Solar Installations',
            ]);

        $response->assertRedirect();

        $media = Media::latest()->first();
        $this->assertNotNull($media);
        $this->assertEquals('solar-panel', $media->name);
        $this->assertEquals('image', $media->type);
        $this->assertEquals('Solar Installations', $media->category);
        $this->assertEquals($owner->id, $media->uploaded_by);
        $this->assertNotNull($media->uploader);
        $this->assertEquals($owner->name, $media->uploader->name);

        Storage::disk('public')->assertExists($media->path);
    }

    public function test_media_can_be_updated_and_deleted(): void
    {
        Storage::fake('public');
        $owner = $this->owner();

        $file = UploadedFile::fake()->image('inverter.jpg', 400, 300);

        $this->actingAs($owner)
            ->post('/admin/media', [
                'file' => $file,
            ]);

        $media = Media::latest()->first();

        $this->actingAs($owner)
            ->put("/admin/media/{$media->id}", [
                'name' => '5kVA Hybrid Inverter',
                'caption' => 'Pure sine wave inverter installed in Lagos',
                'alt' => '5kVA Hybrid Inverter',
                'category' => 'Inverters',
            ])
            ->assertRedirect();

        $media->refresh();
        $this->assertEquals('5kVA Hybrid Inverter', $media->name);
        $this->assertEquals('Inverters', $media->category);

        $this->actingAs($owner)
            ->delete("/admin/media/{$media->id}")
            ->assertRedirect();

        $this->assertSoftDeleted('media', ['id' => $media->id]);
    }
}
