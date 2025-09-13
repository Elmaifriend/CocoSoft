<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Payment;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        
        User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin'),
        ]);

        $user = User::factory()->create([
            'name' => 'Valeria Gonzalez',
            'email' => 'valerina@capuchina.com',
            'password' => Hash::make('admin'),
        ]);

        $clients = Client::factory()->count(10)->create();

        foreach ($clients as $client) {
            $sale = Sale::factory()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'total_amount' => 60000, // Monto alto para asegurar que se puedan generar bonos
            ]);

            // Generamos pagos que suman al menos el 40%
            $paidAmount = 0;
            while (($paidAmount / $sale->total_amount) < 0.4) {
                $paymentAmount = $sale->total_amount * fake()->numberBetween(5, 15) / 100;
                Payment::factory()->create([
                    'sale_id' => $sale->id,
                    'amount' => $paymentAmount,
                ]);
                $paidAmount += $paymentAmount;
            }
        }
    }
}