<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_memberships', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('role', 20);

            $table->timestamps();

            $table->unique(['store_id', 'user_id']);
        });
        DB::statement("
            ALTER TABLE store_memberships
            ADD CONSTRAINT store_memberships_role_check
            CHECK (role IN ('manager', 'staff'))
");
    }

    public function down(): void
    {
        Schema::dropIfExists('store_memberships');
    }
};
