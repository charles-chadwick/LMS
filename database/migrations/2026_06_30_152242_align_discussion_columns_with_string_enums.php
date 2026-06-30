<?php

use App\Enums\DiscussionPostStatus;
use App\Enums\DiscussionStatus;
use App\Enums\DiscussionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert the discussion enum columns to strings cast to PHP enums,
     * matching the courses/pages status convention.
     */
    public function up(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->string('type')->default(DiscussionType::General->value)->change();
            $table->string('status')->default(DiscussionStatus::Open->value)->change();
        });

        Schema::table('discussion_posts', function (Blueprint $table) {
            $table->string('status')->default(DiscussionPostStatus::Published->value)->change();
        });
    }

    /**
     * Restore the original native enum columns.
     */
    public function down(): void
    {
        Schema::table('discussions', function (Blueprint $table) {
            $table->enum('type', ['Private', 'Group'])->default('Private')->change();
            $table->enum('status', ['Open', 'Closed'])->default('Open')->change();
        });

        Schema::table('discussion_posts', function (Blueprint $table) {
            $table->enum('status', ['Published', 'Draft'])->default('Draft')->change();
        });
    }
};
