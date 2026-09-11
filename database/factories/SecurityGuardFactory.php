<?php

namespace Database\Factories;

use App\Models\SecurityGuard;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SecurityGuardFactory extends Factory
{
    protected $model = SecurityGuard::class;

    public function definition(): array
    {
        return [
            'fullname'           => $this->faker->name(),
            'email_address'      => $this->faker->unique()->safeEmail(),
            'phone_number'       => $this->faker->phoneNumber(),
            'license_number'     => 'SIA-' . strtoupper(Str::random(6)),
            'license_exp_date'   => now()->addYears(rand(1, 5)),
            'category'           => $this->faker->randomElement(['SIA', 'Steward']),
            'adresse'            => $this->faker->address(),
            'rtw_share_code'     => strtoupper(Str::random(8)),
            'visa_status'        => $this->faker->randomElement([
                'British', 'Student', 'PSW', 'Dependent', 'Spouse', 'ARC'
            ]),
            'ni_number'          => 'AB' . rand(100000, 999999) . 'C',
            'driving_license'    => $this->faker->randomElement(['UK', 'International', 'No']),
            'car'                => $this->faker->randomElement(['Yes', 'No']),
            'city'               => $this->faker->city(),

            // Documents (fake file paths)
            'profile_picture'    => 'documents/sample/profile.jpg',
            'sia_license'        => 'documents/sample/sia.pdf',
            'driving_license_doc'=> 'documents/sample/license.pdf',
            'passport'           => 'documents/sample/passport.pdf',
            'evisa_ss'            => 'documents/sample/evisa.png',
            'rtw_ss'              => 'documents/sample/rtw.png',
            'proof_add1'          => 'documents/sample/address1.pdf',
            'proof_add2'          => 'documents/sample/address2.pdf',
            'ni_letter'           => 'documents/sample/ni.pdf',
        ];
    }
}
