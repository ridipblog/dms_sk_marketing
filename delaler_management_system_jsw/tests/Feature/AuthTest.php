<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Role;
use App\Models\Company;
use App\Models\UserRoleCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_fails_when_user_has_no_role_mapping(): void
    {
        // 1. Create a user without any role mapping
        $user = User::factory()->create([
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
        ]);

        // 2. Post to login route
        $response = $this->postJson(route('login.submit'), [
            'phone_number' => '1234567890',
            'password' => 'password123',
        ]);

        // 3. Assert success is false, specific message is returned
        $response->assertStatus(200)
            ->assertJson([
                'success' => false,
                'message' => 'No role assigned to this user. Please contact your administrator.'
            ]);

        // 4. Assert user is logged out (guest)
        $this->assertGuest();
    }

    public function test_login_succeeds_when_user_has_role_mapping(): void
    {
        // 1. Create user, role, company, and mapping
        $user = User::factory()->create([
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
        ]);

        $role = Role::create([
            'role_name' => 'Admin',
            'status' => 'active',
        ]);

        $company = Company::create([
            'company_code' => 'COMP01',
            'company_name' => 'JSW Steel',
            'status' => 'active',
        ]);

        UserRoleCompany::create([
            'user_id' => $user->id,
            'role_id' => $role->id,
            'company_id' => $company->id,
        ]);

        // 2. Post to login route
        $response = $this->postJson(route('login.submit'), [
            'phone_number' => '1234567890',
            'password' => 'password123',
        ]);

        // 3. Assert login is successful and redirects
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'redirect_url' => route('dashboard'),
            ]);

        // 4. Assert user is authenticated
        $this->assertAuthenticatedAs($user);
    }
}
