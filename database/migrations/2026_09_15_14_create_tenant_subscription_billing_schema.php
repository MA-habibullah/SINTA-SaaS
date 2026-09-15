<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tambah kolom masa aktif langganan pada tabel core.tenants jika belum ada
        Schema::table('core.tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('core.tenants', 'subscription_expires_at')) {
                $table->timestamp('subscription_expires_at')->nullable()->index();
            }
            if (!Schema::hasColumn('core.tenants', 'subscription_price')) {
                $table->decimal('subscription_price', 15, 2)->default(750000);
            }
            if (!Schema::hasColumn('core.tenants', 'billing_cycle')) {
                $table->string('billing_cycle', 20)->default('monthly'); // monthly, annual, lifetime
            }
            if (!Schema::hasColumn('core.tenants', 'is_locked')) {
                $table->boolean('is_locked')->default(false)->index();
            }
            if (!Schema::hasColumn('core.tenants', 'lock_reason')) {
                $table->string('lock_reason', 255)->nullable();
            }
        });

        // Pastikan default subscription_expires_at terisi untuk tenant eksisting (misal 90 hari ke depan)
        DB::statement("UPDATE core.tenants SET subscription_expires_at = NOW() + INTERVAL '90 days' WHERE subscription_expires_at IS NULL");

        // 2. Buat tabel core.tenant_subscription_invoices untuk menyimpan tagihan SaaS antar-sekolah
        if (!Schema::hasTable('core.tenant_subscription_invoices')) {
            Schema::create('core.tenant_subscription_invoices', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('tenant_id')->index();
                $table->string('invoice_number', 50)->unique();
                $table->string('periode', 100);
                $table->string('nama_paket', 50)->default('Enterprise School Pro');
                $table->decimal('nominal', 15, 2);
                $table->string('status', 30)->default('UNPAID')->index(); // UNPAID, PAID, EXPIRED, CANCELLED
                $table->date('due_date')->index();
                $table->timestamp('paid_at')->nullable();
                $table->string('payment_method', 50)->nullable(); // TRANSFER_MANUAL, MIDTRANS_VA, QRIS, CREDIT_CARD
                $table->string('payment_proof_path', 255)->nullable();
                $table->string('transaction_reference', 100)->nullable();
                $table->text('notes')->nullable();
                $table->uuid('verified_by')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('core.tenants')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core.tenant_subscription_invoices');
    }
};
