<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class BootstrapAdmin extends Command
{
    protected $signature = 'app:bootstrap-admin';
    protected $description = 'Create or update the bootstrap admin from environment variables';

    public function handle(): int
    {
        $email = trim((string) env('ADMIN_EMAIL', ''));
        $password = (string) env('ADMIN_PASSWORD', '');
        $name = trim((string) env('ADMIN_NAME', 'Administrator')) ?: 'Administrator';

        if ($email === '' || $password === '') {
            $this->warn('ADMIN_EMAIL / ADMIN_PASSWORD are not set; skipping admin bootstrap.');
            return self::SUCCESS;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => Hash::make($password), 'role' => 'admin']
        );

        $this->info('Bootstrap admin is ready.');
        return self::SUCCESS;
    }
}
