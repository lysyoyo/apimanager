<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_view_his_profile()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/me')
            ->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }

    /** @test */
    public function user_can_update_profile_info()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $payload = ['name' => 'Updated Name', 'email' => 'updated@example.com'];

        $this->withToken($token)
            ->putJson('/api/profile', $payload)
            ->assertStatus(200)
            ->assertJson([
                'message' => 'Profil mis à jour avec succès.',
                'user' => $payload,
            ]);
    }

    /** @test */
    public function user_can_change_password_with_correct_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword')
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/profile/password', [
                'current_password' => 'oldpassword',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertStatus(200)
            ->assertJson(['message' => 'Mot de passe mis à jour avec succès.']);
    }

    /** @test */
    public function user_cannot_change_password_with_wrong_current_password()
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword')
        ]);
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/profile/password', [
                'current_password' => 'wrongpassword',
                'password' => 'newpassword',
                'password_confirmation' => 'newpassword',
            ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Mot de passe actuel incorrect.']);
    }

    /** @test */
    public function user_can_delete_account()
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)
            ->deleteJson('/api/profile/delete')
            ->assertStatus(200)
            ->assertJson(['message' => 'Votre compte a été supprimé.']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
