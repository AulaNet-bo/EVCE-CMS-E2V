<?php

namespace App\Providers;

use App\Models\RfidTag;
use App\Observers\RfidTagObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') !== 'local') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        RfidTag::observe(RfidTagObserver::class);
        \App\Models\Promotion::observe(\App\Observers\PromotionObserver::class);
        \App\Models\WalletTransaction::observe(\App\Observers\WalletTransactionObserver::class);

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Dynamically load SMTP mail configuration from database settings
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('system_settings')) {
                $settings = \App\Models\SystemSetting::first();
                if ($settings) {
                    config([
                        'app.name' => $settings->platform_name ?: 'Electropoint',
                    ]);

                    if (!empty($settings->mail_host)) {
                        config([
                            'mail.default' => 'smtp',
                            'mail.mailers.smtp.host' => trim($settings->mail_host),
                            'mail.mailers.smtp.port' => (int) trim($settings->mail_port),
                            'mail.mailers.smtp.encryption' => $settings->mail_encryption === 'none' ? null : trim($settings->mail_encryption),
                            'mail.mailers.smtp.username' => trim($settings->mail_username),
                            'mail.mailers.smtp.password' => trim($settings->mail_password),
                            'mail.from.address' => $settings->mail_from_address ? trim($settings->mail_from_address) : config('mail.from.address'),
                            'mail.from.name' => $settings->mail_from_name ? trim($settings->mail_from_name) : ($settings->platform_name ? trim($settings->platform_name) : config('mail.from.name')),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently fail during database migrations or initial setup
        }
    }
}
