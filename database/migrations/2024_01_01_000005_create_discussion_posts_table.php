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
        Schema::create('discussion_posts', function (Blueprint $table) {
            $table->id();
            $table->integer('discussion_id');
            $table->enum('status', ['Published', 'Draft'])->default('Draft');
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();
            $table->integer('created_by_id')->default(1);
            $table->integer('updated_by_id')->nullable()->default(1);
            $table->integer('deleted_by_id')->nullable()->default(1);

            $table->foreign('discussion_id')
                ->references('id')
                ->on('discussions')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discussion_posts');
    }
};
