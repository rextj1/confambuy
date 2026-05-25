<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->phoneNumber(),
            'is_active' => true,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SUPER ADMIN STATE
    |--------------------------------------------------------------------------
    */
    public function superAdmin(): static
    {
        return $this->state(fn () => [
            'name' => config('super_admin.name'),
            'email' => config('super_admin.email'),
            'password' => Hash::make(config('super_admin.password')),
            'phone' => 12345678923,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    // /*
    // |--------------------------------------------------------------------------
    // | ADMIN STATE (OPTIONAL BUT USEFUL)
    // |--------------------------------------------------------------------------
    // */
    // public function admin(): static
    // {
    //     return $this->state(fn () => [
    //         'name' => config('admin.name', 'Admin User'),
    //         'email' => config('admin.email', 'admin@example.com'),
    //         'password' => Hash::make(config('admin.password', 'password')),
    //         'phone' => 12345678923,
    //         'is_active' => true,
    //         'email_verified_at' => now(),
    //     ]);
    // }
    
}

