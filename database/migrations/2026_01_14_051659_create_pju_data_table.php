<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pju_data', function (Blueprint $table) {
            $table->id();
            $table->string('idpel', 20)->index();
            $table->string('nama', 255)->nullable();
            $table->string('namapnj', 255)->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('tarif', 20)->nullable();
            $table->integer('daya')->nullable();
            $table->string('jenislayanan', 20)->nullable();
            $table->string('nomor_meter_kwh', 50)->nullable();
            $table->string('nomor_gardu', 50)->nullable();
            $table->string('nomor_jurusan_tiang', 50)->nullable();
            $table->string('nama_gardu', 100)->nullable();
            $table->string('nomor_meter_prepaid', 50)->nullable();
            $table->decimal('koordinat_x', 20, 14)->nullable();
            $table->decimal('koordinat_y', 20, 14)->nullable();
            $table->char('kdam', 1)->nullable()->index();
            $table->string('nama_kabupaten', 100)->nullable()->index();
            $table->string('nama_kecamatan', 100)->nullable();
            $table->string('nama_kelurahan', 100)->nullable();
            $table->timestamps();
            
            // Index for faster searches
            $table->index(['koordinat_x', 'koordinat_y']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pju_data');
    }
};
