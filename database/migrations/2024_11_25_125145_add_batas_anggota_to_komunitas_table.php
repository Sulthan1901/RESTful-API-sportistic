<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBatasAnggotaToKomunitasTable extends Migration
{
    public function up()
    {
        Schema::table('komunitas', function (Blueprint $table) {
            // Menambahkan kolom 'batas_anggota' setelah kolom 'jumlah_anggota'
            $table->integer('batas_anggota')->after('jumlah_anggota')->nullable();
        });
    }

    public function down()
    {
        // Jika migrasi dibatalkan, hapus kolom 'batas_anggota'
        Schema::table('komunitas', function (Blueprint $table) {
            $table->dropColumn('batas_anggota');
        });
    }
}
