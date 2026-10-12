<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->unsignedSmallInteger('founded_year')->nullable()->after('company_size');
            $table->text('address')->nullable()->after('description');
            $table->string('linkedin_url')->nullable()->after('website');
            $table->string('facebook_url')->nullable()->after('linkedin_url');
            $table->string('twitter_url')->nullable()->after('facebook_url');
            $table->string('instagram_url')->nullable()->after('twitter_url');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'founded_year',
                'address',
                'linkedin_url',
                'facebook_url',
                'twitter_url',
                'instagram_url',
            ]);
        });
    }
};
