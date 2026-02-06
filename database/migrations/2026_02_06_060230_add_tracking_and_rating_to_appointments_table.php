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
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('employee_latitude')->nullable();
            $table->string('employee_longitude')->nullable();
            $table->tinyInteger('rating')->nullable()->comment('1 to 5 stars');
            $table->text('review_comment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['employee_latitude', 'employee_longitude', 'rating', 'review_comment']);
        });
    }
};
