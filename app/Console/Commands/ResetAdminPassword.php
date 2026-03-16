<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ResetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reset-admin-password';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = \App\Models\User::all();

        if ($users->isEmpty()) {
            $this->error('No users found in the database!');
            return;
        }

        foreach ($users as $user) {
            $user->password = \Illuminate\Support\Facades\Hash::make('password');
            $user->save();
            $this->info("User found: {$user->email}");
            $this->info("Password forcibly reset to: password");
            $this->line("--------------------------------------------------");
        }

        $this->info("Done! Please use one of the emails above to login.");
    }
}
