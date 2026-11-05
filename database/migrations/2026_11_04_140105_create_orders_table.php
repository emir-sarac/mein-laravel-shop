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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Status: 1 = pending, 2 = done, 3 = canceled
            $table->unsignedTinyInteger('status')->default(1)->comment('1=pending, 2=done, 3=canceled');

            // Lieferadresse (Lieferung) als Text eingefroren
            $table->string('deliver_address1');
            $table->string('deliver_address2')->nullable();
            $table->string('deliver_postal_code');
            $table->string('deliver_city');
            $table->string('deliver_country');

            // Rechnungsadresse (Invoice) als Text eingefroren
            $table->string('invoice_address1');
            $table->string('invoice_address2')->nullable();
            $table->string('invoice_postal_code');
            $table->string('invoice_city');
            $table->string('invoice_country');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
