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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique(); // QR/Barcode identifier
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('asset_categories')->onDelete('restrict');
            $table->string('location_type')->default('room'); // room, floor, building, outdoor
            $table->string('location_identifier'); // Room number, floor, building name
            $table->string('floor')->nullable();
            $table->string('building')->nullable();
            $table->decimal('latitude', 10, 8)->nullable(); // For outdoor assets
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->date('install_date')->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->integer('expected_lifespan_months')->nullable();
            $table->json('specifications')->nullable();
            $table->json('manuals')->nullable(); // Array of file paths
            $table->string('status')->default('active'); // active, inactive, under_maintenance, decommissioned
            $table->integer('health_score')->default(100); // 0-100 calculated score
            $table->timestamp('last_service_date')->nullable();
            $table->timestamp('next_service_due')->nullable();
            $table->integer('total_work_orders')->default(0);
            $table->integer('failure_count')->default(0);
            $table->decimal('total_maintenance_cost', 12, 2)->default(0);
            $table->boolean('is_critical')->default(false); // Critical infrastructure
            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index(['location_type', 'location_identifier']);
            $table->index('asset_tag');
            $table->index('warranty_expiry');
            $table->index('next_service_due');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
