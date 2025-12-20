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
        Schema::create('courses_users', function (Blueprint $table) {
            $table->id();
            $table->integer('course_id');
            $table->integer('user_id');
            $table->boolean('is_instructor')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->integer('created_by_id')->default(1);
            $table->integer('updated_by_id')->nullable()->default(1);
            $table->integer('deleted_by_id')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses_users');
    }
};
