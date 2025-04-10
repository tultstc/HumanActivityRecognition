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
        Schema::create('camera_group', function (Blueprint $table) {
            $table->primary(['camera_id', 'group_id']);
            $table->unsignedBigInteger('camera_id');
            $table->unsignedBigInteger('group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('camera_group');
    }
};