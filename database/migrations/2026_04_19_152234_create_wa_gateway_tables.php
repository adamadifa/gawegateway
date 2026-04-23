<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->enum('status', ['disconnected', 'connecting', 'connected', 'banned'])->default('disconnected');
            
            // Anti-Ban Settings
            $table->boolean('typing_simulation')->default(true);
            $table->integer('min_delay')->default(3000); // 3 seconds
            $table->integer('max_delay')->default(10000); // 10 seconds
            
            $table->json('session_data')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('remote_jid');
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('type', ['text', 'image', 'document', 'video', 'audio'])->default('text');
            $table->text('content')->nullable();
            $table->string('media_path')->nullable();
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('phone');
            $table->string('name')->nullable();
            $table->string('pushname')->nullable();
            $table->timestamps();
            
            $table->unique(['device_id', 'phone']);
        });

        Schema::create('auto_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->onDelete('cascade');
            $table->string('keyword');
            $table->enum('type', ['equal', 'contain'])->default('equal');
            $table->text('reply');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_replies');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('devices');
    }
};
