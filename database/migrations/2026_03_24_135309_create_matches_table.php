<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->integer('round_number');
            $table->integer('match_position');

            // Players
            $table->foreignId('player1_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('player2_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Scores
            $table->integer('player1_score')->nullable();
            $table->integer('player2_score')->nullable();

            // Winner
            $table->foreignId('winner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Next match (bracket system)
            $table->foreignId('next_match_id')
                ->nullable()
                ->constrained('matches')
                ->nullOnDelete();

            $table->enum('status', [
                'pending',
                'in_progress',
                'finished'
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
