<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create ' .
        '{--e|email= : E-Mail of the newly created user.} ' .
        '{--f|first_name= : First name of the newly created user.} ' .
        '{--l|last_name= : Last name of the newly created user.} ' .
        '{--p|password= : Password of the newly created user.}' .
        '{--s|super-admin : Assign the super-admin role to the user.} ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually creates a new user.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->option('email') ?? $this->ask('What is the email address?');
        $first_name = $this->option('first_name') ?? $this->ask('What is the first name?');
        $last_name = $this->option('last_name') ?? $this->ask('What is the last name?');
        if ($this->option('password') == null) {
            $password = $this->secret('What is the password?');
            $password_confirmation = $this->secret('Please confirm the password.');
            if ($password !== $password_confirmation) {
                $this->error('The passwords do not match.');
                return Command::FAILURE;
            }
        } else {
            $password = $this->option('password');
        }
        DB::beginTransaction();
        try {
            $user = User::create([
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);
            if ($this->option('super-admin') != null) {
                $user->assignRole('super-admin');
            }
            $user->save();
            $this->info('User created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('An error occurred while creating the user.');
            $this->error($e->getMessage());
            return Command::FAILURE;
        }
        DB::commit();
        return Command::SUCCESS;
    }
}
