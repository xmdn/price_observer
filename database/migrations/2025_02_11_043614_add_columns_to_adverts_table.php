<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('adverts', function (Blueprint $table) {
            // Step 1: Add advert_id as nullable first
            $table->string('advert_id')->nullable()->after('id');
            $table->string('status')->default('active')->after('price');
            $table->string('category')->nullable()->after('status');
            $table->unsignedBigInteger('user_id')->nullable()->after('category');
            $table->string('location')->nullable()->after('user_id');
            $table->timestamp('last_checked_at')->nullable()->after('updated_at');
        });

        // Step 2: Populate advert_id for existing rows (set to a default value)
        DB::table('adverts')->update(['advert_id' => DB::raw("id::text")]);

        // Step 3: Make advert_id NOT NULL
        Schema::table('adverts', function (Blueprint $table) {
            $table->string('advert_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adverts', function (Blueprint $table) {
            $table->dropColumn([
                'advert_id',
                'status',
                'category',
                'user_id',
                'location',
                'last_checked_at'
            ]);
        });
    }
};
