<?php

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('group_users', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Group::class);
            $table->foreignIdFor(User::class);
            $table->boolean('is_leader');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('group_users');
    }
};
