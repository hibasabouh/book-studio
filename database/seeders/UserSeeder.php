<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed a known admin + demo user (password: "password") plus a few
     * random users. Idempotent for the named accounts via firstOrNew.
     */
    public function run(): void
    {
        $this->upsert('Admin', 'admin@bookstudio.test', Role::Admin);
        $this->upsert('Demo User', 'user@bookstudio.test', Role::User);

        User::factory()->count(8)->create();
    }

    /**
     * Create or update a user by email. `role` is set directly (not mass
     * assigned) because it is intentionally excluded from $fillable.
     */
    private function upsert(string $name, string $email, Role $role): void
    {
        $user = User::firstOrNew(['email' => $email]);
        $user->name = $name;
        $user->password = 'password';
        $user->role = $role;
        $user->email_verified_at = now();
        $user->save();
    }
}
