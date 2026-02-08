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
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'price')) {
                $table->decimal('price', 10, 2)->nullable();
            }
            if (!Schema::hasColumn('services', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable();
            }
            if (!Schema::hasColumn('services', 'location_type')) {
                $table->enum('location_type', ['onsite', 'delivery', 'both'])->default('onsite');
            }
            if (!Schema::hasColumn('services', 'company_id')) {
                $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'company_id')) {
                // Check for foreign key existence before dropping? tricky. 
                // Assuming standard naming or just try catch block in raw sql if needed.
                // For now, let's just drop the column. Dropping column drops FK in some DBs but not all.
                // Laravel usually handles it if using dropForeign first.
                // But we don't know the index name easily. 
                // Let's just drop the columns for now.
                 $table->dropForeign(['company_id']);
                 $table->dropColumn('company_id');
            }
            
            $columnsToDrop = [];
            if (Schema::hasColumn('services', 'price')) $columnsToDrop[] = 'price';
            if (Schema::hasColumn('services', 'duration_minutes')) $columnsToDrop[] = 'duration_minutes';
            if (Schema::hasColumn('services', 'location_type')) $columnsToDrop[] = 'location_type';
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
