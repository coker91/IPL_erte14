<?php

namespace App\Filament\Auth;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

class Login extends \Filament\Auth\Pages\Login
{
    public function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('username')->label('Username')->required()->autocomplete('username')->autofocus(), $this->getPasswordFormComponent(), $this->getRememberFormComponent()]);
    }

    protected function getCredentialsFromFormData(#[\SensitiveParameter] array $data): array
    {
        return ['username' => $data['username'], 'password' => $data['password'], 'is_active' => true];
    }

    protected function throwFailureValidationException(): never
    {
        throw ValidationException::withMessages(['data.username' => 'Username atau password tidak sesuai, atau akun tidak aktif.']);
    }
}
