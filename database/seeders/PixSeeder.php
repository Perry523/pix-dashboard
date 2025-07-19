<?php

namespace Database\Seeders;

use App\Models\Pix;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PixSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->error('Nenhum usuário encontrado. Execute primeiro o UserSeeder.');
            return;
        }

        $this->command->info('Criando 1000 registros PIX...');

        $this->command->info('Criando 500 PIX pagos...');
        for ($i = 0; $i < 500; $i++) {
            Pix::create([
                'user_id' => $user->id,
                'token' => Str::uuid(),
                'status' => Pix::STATUS_PAID,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()->subDays(rand(1, 30)),
            ]);

            if ($i % 100 === 0) {
                $this->command->info("Criados {$i}/500 PIX pagos...");
            }
        }

        $this->command->info('Criando 500 PIX expirados...');
        for ($i = 0; $i < 500; $i++) {
            Pix::create([
                'user_id' => $user->id,
                'token' => Str::uuid(),
                'status' => Pix::STATUS_EXPIRED,
                'expires_at' => now()->subMinutes(rand(10, 1440)),
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now()->subDays(rand(1, 30)),
            ]);

            if ($i % 100 === 0) {
                $this->command->info("Criados {$i}/500 PIX expirados...");
            }
        }

        $this->command->info('1000 registros PIX criados com sucesso!');
    }
}
