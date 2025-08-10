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
        // Add indexes for experiences table to improve query performance
        Schema::table('experiences', function (Blueprint $table) {
            $table->index(['year_start'], 'idx_experiences_year_start');
            $table->index(['year_end'], 'idx_experiences_year_end');
            $table->index(['currently_working'], 'idx_experiences_currently_working');
            $table->index(['year_start', 'year_end'], 'idx_experiences_date_range');
        });

        // Add indexes for education table
        Schema::table('education', function (Blueprint $table) {
            $table->index(['year_start'], 'idx_education_year_start');
            $table->index(['year_end'], 'idx_education_year_end');
            $table->index(['year_start', 'year_end'], 'idx_education_date_range');
        });

        // Add indexes for projects table (for recent projects performance)
        Schema::table('projects', function (Blueprint $table) {
            $table->index(['created_at'], 'idx_projects_created_at');
            $table->index(['category_id'], 'idx_projects_category_id');
            $table->index(['created_at', 'category_id'], 'idx_projects_created_category');
        });

        // Add indexes for categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->index(['created_at'], 'idx_categories_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes for experiences table
        Schema::table('experiences', function (Blueprint $table) {
            $table->dropIndex('idx_experiences_year_start');
            $table->dropIndex('idx_experiences_year_end');
            $table->dropIndex('idx_experiences_currently_working');
            $table->dropIndex('idx_experiences_date_range');
        });

        // Remove indexes for education table
        Schema::table('education', function (Blueprint $table) {
            $table->dropIndex('idx_education_year_start');
            $table->dropIndex('idx_education_year_end');
            $table->dropIndex('idx_education_date_range');
        });

        // Remove indexes for projects table
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex('idx_projects_created_at');
            $table->dropIndex('idx_projects_category_id');
            $table->dropIndex('idx_projects_created_category');
        });

        // Remove indexes for categories table
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('idx_categories_created_at');
        });
    }
};
