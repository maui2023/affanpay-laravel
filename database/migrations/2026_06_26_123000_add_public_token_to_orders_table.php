<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->uuid('public_token')->nullable()->after('id');
        });

        DB::table('orders')
            ->select(['id'])
            ->orderBy('id')
            ->chunkById(100, function ($orders): void {
                foreach ($orders as $order) {
                    DB::table('orders')
                        ->where('id', $order->id)
                        ->update(['public_token' => (string) Str::uuid()]);
                }
            });

        Schema::table('orders', function (Blueprint $table) {
            $table->unique('public_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
