<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('server_health_logs')) {
            Schema::table('server_health_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('server_health_logs', 'latency_ms')) {
                    $table->unsignedInteger('latency_ms')->nullable()->after('status');
                }
                if (!Schema::hasColumn('server_health_logs', 'packet_loss_pct')) {
                    $table->decimal('packet_loss_pct', 5, 2)->nullable()->after('latency_ms');
                }
                if (!Schema::hasColumn('server_health_logs', 'network_in_mbps')) {
                    $table->decimal('network_in_mbps', 10, 3)->nullable()->after('packet_loss_pct');
                }
                if (!Schema::hasColumn('server_health_logs', 'network_out_mbps')) {
                    $table->decimal('network_out_mbps', 10, 3)->nullable()->after('network_in_mbps');
                }
                if (!Schema::hasColumn('server_health_logs', 'network_status')) {
                    $table->string('network_status', 20)->nullable()->after('network_out_mbps');
                }
            });
        }

        if (Schema::hasTable('servers')) {
            Schema::table('servers', function (Blueprint $table) {
                if (!Schema::hasColumn('servers', 'datacenter')) {
                    $table->string('datacenter')->nullable()->after('nameserver3');
                }
                if (!Schema::hasColumn('servers', 'location')) {
                    $table->string('location')->nullable()->after('datacenter');
                }
                // os_name, os_version, kernel já existem na migration principal (000040)
            });
        }
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
