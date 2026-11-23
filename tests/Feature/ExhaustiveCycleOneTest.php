<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ExhaustiveCycleOneTest extends TestCase
{
    public function test_cycle_1_registration_validation_and_creation(): void
    {
        // 1. Validation error: passwords do not match
        $badResponse = $this->post('/register', [
            'name' => 'Test Student',
            'email' => 'badpass@feder.edu.bo',
            'id_document' => '123456',
            'phone' => '70000000',
            'birth_date' => '2000-01-01',
            'gender' => 'Masculino',
            'password' => 'password123',
            'password_confirmation' => 'mismatch',
        ]);
        $badResponse->assertSessionHasErrors('password');

        // 2. Success: create new test student
        $uniqueEmail = 'student_' . Str::lower(Str::random(8)) . '@feder.edu.bo';
        $goodResponse = $this->post('/register', [
            'name' => 'Estudiante Prueba Ciclo 1',
            'email' => $uniqueEmail,
            'id_document' => '998877 SC',
            'phone' => '+591 78912345',
            'birth_date' => '1998-05-20',
            'gender' => 'Femenino',
            'password' => 'SecurePass123*',
            'password_confirmation' => 'SecurePass123*',
        ]);

        $goodResponse->assertRedirect('/panel');
        $this->assertAuthenticated();

        $created = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($created);
        $this->assertEquals('student', $created->role);
        $this->assertEquals('998877 SC', $created->id_document);
        $this->assertTrue(Hash::check('SecurePass123*', $created->password));

        // Clean up
        $created->delete();
    }

    public function test_cycle_1_authentication_flow_and_throttling(): void
    {
        // 1. Bad credentials
        $res = $this->post('/login', [
            'email' => 'a3ronmartinez@gmail.com',
            'password' => 'wrongpassword',
        ]);
        $res->assertSessionHas('error');
        $this->assertGuest();

        // 2. Successful login
        $res = $this->post('/login', [
            'email' => 'a3ronmartinez@gmail.com',
            'password' => 'password',
        ]);
        $res->assertRedirect('/gestion');
        $this->assertAuthenticated();

        // 3. Logout
        $logoutRes = $this->post('/logout');
        $logoutRes->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_cycle_1_admin_user_management_crud(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->assertNotNull($admin);

        // 1. Admin views user index
        $indexRes = $this->actingAs($admin)->get('/gestion/usuarios');
        $indexRes->assertStatus(200);

        // 2. Create a dummy user to test toggle active and edit
        $dummyEmail = 'testtoggle_' . Str::random(6) . '@feder.edu.bo';
        $user = User::create([
            'name' => 'Usuario Para Desactivar',
            'email' => $dummyEmail,
            'password' => 'password',
            'role' => 'student',
            'is_active' => true,
        ]);

        // Toggle inactive via admin update
        $toggleRes = $this->actingAs($admin)->put("/gestion/usuarios/{$user->id}", [
            'name' => $user->name,
            'email' => $dummyEmail,
            'role' => 'student',
            'is_active' => false,
        ]);
        $toggleRes->assertSessionHas('success');
        $user->refresh();
        $this->assertFalse($user->is_active);

        // Inactive user cannot log in
        Auth::logout();
        $loginBlocked = $this->post('/login', [
            'email' => $dummyEmail,
            'password' => 'password',
        ]);
        $loginBlocked->assertSessionHas('error');
        $this->assertGuest();

        // Admin re-activates user and updates role
        $this->actingAs($admin)->put("/gestion/usuarios/{$user->id}", [
            'name' => 'Usuario Nombre Actualizado',
            'email' => $dummyEmail,
            'role' => 'instructor',
            'is_active' => true,
        ]);
        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertEquals('instructor', $user->role);

        // Clean up
        $user->delete();
    }
}
