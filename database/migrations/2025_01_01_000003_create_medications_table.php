<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('rxcui')->index();
            $table->string('name')->nullable();
            $table->json('ingredient_base_names')->nullable();
            $table->json('dose_form_names')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'rxcui']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medications');
    }
};

