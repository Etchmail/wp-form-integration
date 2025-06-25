<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->string('contact')->nullable();
            $table->string('location')->nullable();
            $table->json('social_links')->nullable();
            $table->text('bio')->nullable();
            $table->text('summary')->nullable();
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('profiles');
    }
};
