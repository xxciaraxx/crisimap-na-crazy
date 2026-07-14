<?php

namespace Database\Seeders;

use App\Models\AssistanceType;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class BeneficiarySeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_PH');
        $barangays = Barangay::pluck('id')->all();
        $types = AssistanceType::pluck('id')->all();
        $users = User::pluck('id')->all();

        for ($i = 0; $i < 50; $i++) {
            Beneficiary::create([
                'name' => $faker->name(),
                'age' => $faker->numberBetween(18, 80),
                'sex' => $faker->randomElement(['Male', 'Female', 'Other']),
                'contact' => $faker->phoneNumber(),
                'barangay_id' => $faker->randomElement($barangays),
                'assistance_type_id' => $faker->randomElement($types),
                'address' => $faker->streetAddress().', '.$faker->randomElement($barangays ? ['Tagudin'] : ['Tagudin']),
                'amount' => $faker->randomFloat(2, 500, 10000),
                'latitude' => $faker->latitude(16.8, 17.0),
                'longitude' => $faker->longitude(120.2, 120.5),
                'case_info' => $faker->sentence(),
                'date_of_assistance' => $faker->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
                'status' => $faker->randomElement(['Pending', 'Approved', 'Released', 'Completed']),
                'encoded_by' => $faker->randomElement($users),
            ]);
        }
    }
}
