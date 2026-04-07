<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_health_logs', function (Blueprint $table) {
            $table->unsignedInteger('latency_ms')->nullable()->after('status');
            $table->decimal('packet_loss_pct', 5, 2)->nullable()->after('latency_ms');
            $table->decimal('network_in_mbps', 10, 3)->nullable()->after('packet_loss_pct');
            $table->decimal('network_out_mbps', 10, 3)->nullable()->after('network_in_mbps');
            $table->string('network_status', 20)->nullable()->after('network_out_mbps'); // online|degraded|offline
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->string('datacenter')->nullable()->after('nameserver3');
            $table->string('location')->nullable()->after('datacenter');
            $table->string('os_name', 50)->nullable()->after('location');
            $table->string('os_version', 50)->nullable()->after('os_name');
            $table->string('kernel', 100)->nullable()->after('os_version');
        });
    }

    public function down(): void
    {
        Schema::table('server_health_logs', function (Blueprint $table) {
            $table->dropColumn(['latency_ms', 'packet_loss_pct', 'network_in_mbps', 'network_out_mbps', 'network_status']);
        });

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['datacenter', 'location', 'os_name', 'os_version', 'kernel']);
        });
    }
};
