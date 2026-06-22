<?php

namespace Tests\Feature\Auth;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@student.ub.ac.id',
            'password' => 'password',
            'password_confirmation' => 'password',
            'no_induk' => '195020100099',
            'prodi' => 'S1 Ilmu Aktuaria',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('students', [
            'no_induk' => '195020100099',
            'prodi' => 'S1 Ilmu Aktuaria',
        ]);
    }

    public function test_non_student_ub_email_is_rejected(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'no_induk' => '195020100099',
            'prodi' => 'S1 Ilmu Aktuaria',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_no_induk_must_be_unique(): void
    {
        Student::factory()->create(['no_induk' => '195020100099']);

        $response = $this->post('/register', [
            'name' => 'Test User 2',
            'email' => 'test2@student.ub.ac.id',
            'password' => 'password',
            'password_confirmation' => 'password',
            'no_induk' => '195020100099',
            'prodi' => 'S1 Matematika',
        ]);

        $response->assertSessionHasErrors('no_induk');
        $this->assertGuest();
    }

    public function test_prodi_must_be_one_of_the_allowed_options(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@student.ub.ac.id',
            'password' => 'password',
            'password_confirmation' => 'password',
            'no_induk' => '195020100099',
            'prodi' => 'S1 Teknik Informatika',
        ]);

        $response->assertSessionHasErrors('prodi');
        $this->assertGuest();
    }

    public function test_no_induk_must_be_numeric(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@student.ub.ac.id',
            'password' => 'password',
            'password_confirmation' => 'password',
            'no_induk' => 'ABC123XYZ',
            'prodi' => 'S1 Ilmu Aktuaria',
        ]);

        $response->assertSessionHasErrors('no_induk');
        $this->assertGuest();
    }
}
