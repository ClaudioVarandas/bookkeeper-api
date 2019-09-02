<?php

use Bookkeeper\Models\User;
use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = new User();
        $user->name = 'Claudio Varandas';
        $user->email = 'cvarandas@gmail.com';
        $user->email_verified_at = now();
        $user->password = bcrypt("1234");
        $user->save();

        $user->rollApiKey();

    }
}
