<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discount_usages', function (Blueprint $table) {
            $table->dropForeign(['discount_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['order_id']);
            $table->dropColumn('id');
            $table->uuid('id')->primary();
            $table->uuid('discount_id')->change();
            $table->uuid('user_id')->change();
            $table->uuid('order_id')->nullable()->change();
            
            $table->foreign('discount_id')->references('id')->on('discounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('discount_usages', function (Blueprint $table) {
            $table->dropForeign(['discount_id']);
            $table->dropForeign(['user_id']);
            $table->dropForeign(['order_id']);
            $table->dropColumn('id');
            $table->id();
            
            $table->foreign('discount_id')->references('id')->on('discounts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
        });
    }
};
