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
        Schema::table('users', function (Blueprint $table) {
            // Nullable because Super Admin implies no specific company
            $table->foreignId('company_id')->nullable()->constrained()->onDelete('cascade');
            
            // Allow same email in different companies, but unique per company.
            // Note: Super Admin (null company_id) -> Global uniqueness logic might need check or specialized handling.
            // For now, unique(['email', 'company_id']) works fine for non-nulls.
            $table->unique(['email', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
