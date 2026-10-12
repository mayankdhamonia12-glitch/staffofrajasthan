<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidate_profile_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_profile_id')->constrained()->cascadeOnDelete();
            $table->char('visitor_fingerprint', 64);
            $table->timestamp('viewed_at')->index();
            $table->index(['candidate_profile_id', 'visitor_fingerprint', 'viewed_at'], 'profile_view_deduplication_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidate_profile_views');
    }
};
