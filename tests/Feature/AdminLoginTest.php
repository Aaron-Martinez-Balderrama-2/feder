<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    public function test_admin_can_authenticate_with_user_provided_email(): void
    {
        $response = $this->post('/login', [
            'email' => 'a3ronmartinez@gmail.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/gestion');
        $this->assertAuthenticated();
        $this->assertEquals('a3ronmartinez@gmail.com', Auth::user()->email);
        $this->assertEquals('admin', Auth::user()->role);
    }

    public function test_docente_can_authenticate_with_demo_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'docente@feder.edu.bo',
            'password' => 'password',
        ]);

        $response->assertRedirect('/gestion');
        $this->assertAuthenticated();
        $this->assertEquals('docente@feder.edu.bo', Auth::user()->email);
        $this->assertEquals('instructor', Auth::user()->role);
    }

    public function test_estudiante_can_authenticate_with_demo_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'estudiante@feder.edu.bo',
            'password' => 'password',
        ]);

        $response->assertRedirect('/panel');
        $this->assertAuthenticated();
        $this->assertEquals('estudiante@feder.edu.bo', Auth::user()->email);
        $this->assertEquals('student', Auth::user()->role);
    }
}
