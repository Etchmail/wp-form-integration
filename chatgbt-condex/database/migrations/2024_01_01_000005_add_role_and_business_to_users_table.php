<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('business_user');
            $table->foreignId('reseller_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['reseller_id']);
            $table->dropColumn(['reseller_id', 'business_id', 'role']);
        });
    }
};
