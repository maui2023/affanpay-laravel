<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Set initial environment
        Setting::set('affanpay_environment', 'sandbox');
        
        // Set initial sandbox credentials
        Setting::set('affanpay_sandbox_email', '');
        Setting::set('affanpay_sandbox_password', '');
        
        // Set initial live credentials
        Setting::set('affanpay_live_email', '');
        Setting::set('affanpay_live_password', '');
    }
}

