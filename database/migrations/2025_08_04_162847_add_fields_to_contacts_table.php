<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
              $table->string('name');
        $table->string('email');
        $table->string('phone')->nullable();
        $table->string('website')->nullable();
        $table->text('message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
                    $table->dropColumn(['name', 'email', 'phone', 'website', 'message']);
        });
    }
};
