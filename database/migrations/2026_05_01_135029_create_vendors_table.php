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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('mobile')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('USA');
            $table->string('postal_code')->nullable();
            $table->string('website')->nullable();
            $table->string('vendor_type')->nullable(); // contractor, supplier, manufacturer, service_provider
            $table->json('specializations')->nullable(); // HVAC, Electrical, Elevator, Fire Safety, etc.
            $table->string('license_number')->nullable();
            $table->string('insurance_policy')->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('rating', 3, 2)->nullable(); // 0-5 rating
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['vendor_type', 'is_active']);
        });

        Schema::create('spare_parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('unit_of_measure')->default('each'); // each, box, liter, meter, etc.
            $table->decimal('cost_per_unit', 10, 2)->default(0);
            $table->integer('quantity_in_stock')->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->integer('max_stock_level')->nullable();
            $table->integer('reorder_point')->default(10);
            $table->integer('reorder_quantity')->default(20);
            $table->string('location')->nullable(); // Storage location (shelf, bin, room)
            $table->json('compatible_assets')->nullable(); // Asset IDs or models this part fits
            $table->boolean('is_critical')->default(false);
            $table->boolean('track_serial')->default(false);
            $table->date('last_ordered_at')->nullable();
            $table->date('last_received_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index('quantity_in_stock');
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('file_name');
            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('file_size'); // in bytes
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('url')->nullable();
            
            // Polymorphic relations
            $table->morphs('attachable'); // work_order, complaint, asset, pm_task, etc.
            
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('restrict');
            $table->text('caption')->nullable();
            $table->string('attachment_type')->nullable(); // photo, video, document, signature
            $table->json('metadata')->nullable(); // EXIF data, dimensions, etc.
            $table->timestamps();

            $table->index(['attachable_type', 'attachable_id'], 'idx_attachable');
            $table->index('attachment_type', 'idx_attachment_type');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // work_order_assigned, sla_breach, warranty_expiry, etc.
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();
            $table->string('channel')->default('in_app'); // in_app, email, sms, push
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // Related entities
            $table->morphs('notifiable'); // work_order, complaint, asset, schedule, etc.
            
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['type', 'created_at']);
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('spare_parts');
        Schema::dropIfExists('vendors');
    }
};
