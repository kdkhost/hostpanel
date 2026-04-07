<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migração de segurança:
 * 1. Adiciona coluna `password` (text) na tabela servers
 * 2. Converte api_hash de string(255) para text (suportar ciphertext AES)
 * 3. Re-criptografa valores existentes em texto plano de api_key, api_hash e password
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('servers')) {
            return;
        }

        Schema::table('servers', function (Blueprint $table) {
            // Adiciona password se não existir
            if (!Schema::hasColumn('servers', 'password')) {
                $table->text('password')->nullable()->after('api_hash')
                      ->comment('Senha de acesso — armazenada criptografada (AES-256)');
            }
        });

        // Re-criptografa valores existentes em texto plano
        // Detecta se já é ciphertext Laravel (começa com "eyJpdiI6") — não criptografa duas vezes
        DB::table('servers')->orderBy('id')->each(function ($server) {
            $updates = [];

            foreach (['api_key', 'api_hash', 'password'] as $field) {
                $value = $server->{$field} ?? null;
                if (!empty($value) && !static::isCiphertext($value)) {
                    $updates[$field] = Crypt::encryptString($value);
                }
            }

            if (!empty($updates)) {
                DB::table('servers')->where('id', $server->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            if (Schema::hasColumn('servers', 'password')) {
                $table->dropColumn('password');
            }
            $table->string('api_hash')->nullable()->change();
        });
    }

    /**
     * Verifica se o valor já é um ciphertext Laravel (serializado como JSON base64).
     * O payload gerado por Crypt::encryptString() é um JSON base64 que começa com "eyJ"
     * após decodificação, representando {"iv":...,"value":...,"mac":...}
     */
    private static function isCiphertext(?string $value): bool
    {
        if (empty($value)) return false;
        try {
            $decoded = base64_decode($value, strict: true);
            if ($decoded === false) return false;
            $payload = json_decode($decoded, true);
            return isset($payload['iv'], $payload['value'], $payload['mac']);
        } catch (\Throwable) {
            return false;
        }
    }
};
