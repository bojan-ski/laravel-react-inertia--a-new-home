<?php

use App\Enums\ListingStatus;
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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();

            $table->string('slug')->unique();

            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('agency_id')->nullable();

            $table->string('title');
            $table->string('listing_type', 20);
            $table->string('property_type', 20);
            $table->string('status', 20)->default(ListingStatus::PendingReview->value)->index();
            $table->text('rejection_reason')->nullable();

            $table->decimal('price', 12, 2);
            $table->string('currency')->default('EUR');
            $table->longText('description');

            $table->string('country');
            $table->string('city');
            $table->string('address_line')->nullable();
            $table->string('postal_code')->nullable();

            $table->unsignedInteger('bedrooms')->nullable();
            $table->unsignedInteger('bathrooms')->nullable();
            $table->decimal('area_value', 10, 2)->nullable();
            $table->string('area_unit')->nullable();
            $table->unsignedSmallInteger('year_built')->nullable();

            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();

            $table->timestamp('approved_at')->nullable()->index();
            $table->timestamp('rejected_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('listing_type');
            $table->index('property_type');
            $table->index(['status', 'listing_type', 'property_type', 'city']);
            $table->index(['status', 'price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
