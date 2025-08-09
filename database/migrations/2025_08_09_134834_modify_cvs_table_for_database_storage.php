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
        Schema::table('cvs', function (Blueprint $table) {
            // Remove the old file path columns
            $table->dropColumn(['cv_fr', 'cv_en']);

            // Add new columns for database storage
            $table->longText('cv_fr_data')->nullable(); // Base64 encoded PDF data
            $table->string('cv_fr_filename')->nullable(); // Original filename
            $table->string('cv_fr_mime_type')->default('application/pdf'); // MIME type
            $table->integer('cv_fr_size')->nullable(); // File size in bytes

            $table->longText('cv_en_data')->nullable(); // Base64 encoded PDF data
            $table->string('cv_en_filename')->nullable(); // Original filename
            $table->string('cv_en_mime_type')->default('application/pdf'); // MIME type
            $table->integer('cv_en_size')->nullable(); // File size in bytes

            // Add metadata
            $table->timestamp('cv_fr_uploaded_at')->nullable();
            $table->timestamp('cv_en_uploaded_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cvs', function (Blueprint $table) {
            // Remove the new columns
            $table->dropColumn([
                'cv_fr_data',
                'cv_fr_filename',
                'cv_fr_mime_type',
                'cv_fr_size',
                'cv_fr_uploaded_at',
                'cv_en_data',
                'cv_en_filename',
                'cv_en_mime_type',
                'cv_en_size',
                'cv_en_uploaded_at'
            ]);

            // Restore the old columns
            $table->string('cv_fr')->nullable();
            $table->string('cv_en')->nullable();
        });
    }
};
