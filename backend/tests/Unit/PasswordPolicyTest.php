<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordPolicyTest extends TestCase
{
    #[DataProvider('rejectedPasswords')]
    public function test_weak_passwords_are_rejected(string $password): void
    {
        $this->assertTrue(Validator::make(['password' => $password], ['password' => Password::defaults()])->fails());
    }

    public function test_strong_password_is_accepted(): void
    {
        $this->assertTrue(Validator::make(['password' => 'Correct-Horse-9'], ['password' => Password::defaults()])->passes());
    }

    public static function rejectedPasswords(): array
    {
        return [
            'too short' => ['Short-Pass1'],
            'no uppercase' => ['correct-horse-battery-9'],
            'no lowercase' => ['CORRECT-HORSE-BATTERY-9'],
            'no number' => ['Correct-Horse-Battery'],
        ];
    }
}
