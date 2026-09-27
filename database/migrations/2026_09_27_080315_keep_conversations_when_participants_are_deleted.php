<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['listing_id']);
            $table->dropForeign(['buyer_id']);
            $table->dropForeign(['owner_id']);

            $table->unsignedBigInteger('listing_id')->nullable()->change();
            $table->unsignedBigInteger('buyer_id')->nullable()->change();
            $table->unsignedBigInteger('owner_id')->nullable()->change();
            $table->string('listing_title')->nullable()->after('listing_id');

            $table->foreign('listing_id')->references('id')->on('listings')->nullOnDelete();
            $table->foreign('buyer_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->unsignedBigInteger('sender_id')->nullable()->change();
            $table->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
        });

        DB::table('chat_messages')->whereNull('sender_id')->delete();

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->unsignedBigInteger('sender_id')->nullable(false)->change();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['listing_id']);
            $table->dropForeign(['buyer_id']);
            $table->dropForeign(['owner_id']);
        });

        DB::table('conversations')
            ->whereNull('listing_id')
            ->orWhereNull('buyer_id')
            ->orWhereNull('owner_id')
            ->delete();

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn('listing_title');
            $table->unsignedBigInteger('listing_id')->nullable(false)->change();
            $table->unsignedBigInteger('buyer_id')->nullable(false)->change();
            $table->unsignedBigInteger('owner_id')->nullable(false)->change();

            $table->foreign('listing_id')->references('id')->on('listings')->cascadeOnDelete();
            $table->foreign('buyer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
