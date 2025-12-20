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
        Schema::create('discussions', function (Blueprint $table) {
            $table->id();
            $table->integer('on');
            $table->string('on_type');
            $table->enum('type', ['Private', 'Group'])->default('Private');
            $table->string('title')->nullable();
            $table->enum('status', ['Open', 'Closed'])->default('Open');
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('discussions');
    }
};
