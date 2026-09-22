<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropUnique(['event_id']);
            $table->unique(['webhook_endpoint_id', 'event_id'], 'webhook_deliveries_endpoint_event_unique');
        });
    }

    public function down(): void
    {
        Schema::table('webhook_deliveries', function (Blueprint $table) {
            $table->dropUnique('webhook_deliveries_endpoint_event_unique');
            $table->unique('event_id');
        });
    }
};
