<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->string('full_name');
            $table->string('phone', 20);
            $table->string('alternate_phone', 20)->nullable();

            $table->text('address_line_1');
            $table->text('address_line_2')->nullable();

            $table->string('city');
            $table->string('state');
            $table->string('postal_code', 20);
            $table->string('country')->default('India');

            $table->string('label')->nullable(); // "Home", "Office", "Parents' House"
            $table->enum('type', ['home', 'work', 'other'])->default('home');
            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });

        Schema::table('user_addresses', function (Blueprint $table) {
            $table->index('user_id');
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};