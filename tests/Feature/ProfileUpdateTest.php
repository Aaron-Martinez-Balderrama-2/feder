<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    public function test_user_can_view_profile_edit_page(): void
    {
        $user = User::where('email', 'a3ronmartinez@gmail.com')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->get('/perfil');
        $response->assertStatus(200);
    }

    public function test_user_can_update_profile_with_picture_without_truncation(): void
    {
        $user = User::where('email', 'a3ronmartinez@gmail.com')->first();
        $this->assertNotNull($user);

        // 1x1 transparent/white pixel jpeg in base64
        $tinyJpegBase64 = 'data:image/jpeg;base64,/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////wgALCAABAAEBAREA/8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPxA=';

        $response = $this->actingAs($user)->post('/perfil', [
            'name' => 'Aaron Martinez',
            'id_document' => '12954604 SC',
            'phone' => '+591 70031990',
            'birth_date' => '2002-07-16',
            'gender' => 'Masculino',
            'profile_picture_base64' => $tinyJpegBase64,
            'remove_picture' => false,
        ]);

        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('Aaron Martinez', $user->name);
        $this->assertEquals('12954604 SC', $user->id_document);
        $this->assertEquals('+591 70031990', $user->phone);
        $this->assertEquals('2002-07-16', $user->birth_date?->format('Y-m-d'));
        $this->assertEquals('Masculino', $user->gender);

        // Check avatar path is stored and greater than 30 chars
        $this->assertNotNull($user->avatar);
        $this->assertGreaterThan(30, strlen($user->avatar));
        $this->assertFileExists(public_path($user->avatar));

        // Clean up uploaded file
        if (File::exists(public_path($user->avatar))) {
            File::delete(public_path($user->avatar));
        }
    }

    public function test_user_can_remove_profile_picture(): void
    {
        $user = User::where('email', 'a3ronmartinez@gmail.com')->first();
        $this->assertNotNull($user);

        $response = $this->actingAs($user)->post('/perfil', [
            'name' => 'Aaron Martinez',
            'id_document' => '12954604 SC',
            'phone' => '+591 70031990',
            'birth_date' => '2002-07-16',
            'gender' => 'Masculino',
            'remove_picture' => true,
        ]);

        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertNull($user->profile_picture);
        $this->assertNull($user->avatar);
    }
}
