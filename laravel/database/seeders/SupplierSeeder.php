<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Supplier;
use App\Models\Phone;
use App\Models\Address;
use Faker\Factory as Faker;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('pt_BR');

        // Criar 100 fornecedores
        for ($i = 0; $i < 500; $i++) {
            // Criar o fornecedor
            $supplier = Supplier::create([
                'name' => $faker->company(),
                'email' => $faker->unique()->companyEmail(),
                'document' => $faker->numerify('##############'),
                'document_type' => 'J'
            ]);

            // Criar telefones (1 principal + 1-3 secundários)
            // Telefone principal
            $supplier->phones()->create([
                'number' => $faker->numerify('(##) #####-####'),
                'is_main' => true,
            ]);

            // Telefones secundários (1-3)
            $secondaryPhonesCount = rand(1, 3);
            for ($j = 0; $j < $secondaryPhonesCount; $j++) {
                $supplier->phones()->create([
                    'number' => $faker->numerify('(##) #####-####'),
                    'is_main' => false,
                ]);
            }

            // Criar endereços (1 principal + 1-2 secundários)
            // Endereço principal
            $supplier->addresses()->create([
                'street' => $faker->streetName(),
                'number' => $faker->buildingNumber(),
                'complement' => $faker->optional(0.3)->secondaryAddress(),
                'neighborhood' => $faker->words(2, true),
                'city' => $faker->city(),
                'state' => $faker->stateAbbr(),
                'cep' => $faker->numerify('#####-###'),
                'is_main' => true,
            ]);

            // Endereços secundários (1-2)
            $secondaryAddressCount = rand(1, 2);
            for ($k = 0; $k < $secondaryAddressCount; $k++) {
                $supplier->addresses()->create([
                    'street' => $faker->streetName(),
                    'number' => $faker->buildingNumber(),
                    'complement' => $faker->optional(0.3)->secondaryAddress(),
                    'neighborhood' => $faker->words(2, true),
                    'city' => $faker->city(),
                    'state' => $faker->stateAbbr(),
                    'cep' => $faker->numerify('#####-###'),
                    'is_main' => false,
                ]);
            }
        }
    }
}