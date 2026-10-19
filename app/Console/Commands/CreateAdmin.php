<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'ipl:create-admin';

    protected $description = 'Buat akun admin pertama secara interaktif';

    public function handle(): int
    {
        $username = $this->ask('Username');
        $name = $this->ask('Nama');
        $email = $this->ask('Email');
        $password = $this->secret('Password (minimal 12 karakter)');
        $v = Validator::make(compact('username', 'name', 'email', 'password'), ['username' => 'required|alpha_dash|unique:users', 'name' => 'required', 'email' => 'required|email|unique:users', 'password' => 'required|min:12']);
        if ($v->fails()) {
            $this->error($v->errors()->first());

            return self::FAILURE;
        }
        $u = User::create(compact('username', 'name', 'email', 'password'));
        $u->assignRole('super_admin');
        $this->info('Admin berhasil dibuat. Login menggunakan username.');

        return self::SUCCESS;
    }
}
