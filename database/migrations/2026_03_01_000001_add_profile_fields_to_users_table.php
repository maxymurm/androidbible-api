<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('locale', 10)->default('en')->after('avatar');
            $table->string('timezone', 50)->default('UTC')->after('locale');
            $table->string('provider')->nullable()->after('password');
            $table->string('provider_id')->nullable()->after('provider');
            $table->text('bio')->nullable()->after('provider_id');
            $table->timestamp('last_login_at')->nullable()->after('bio');
            $table->softDeletes();

            $table->index('provider');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar', 'locale', 'timezone',
                'provider', 'provider_id', 'bio', 'last_login_at',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
