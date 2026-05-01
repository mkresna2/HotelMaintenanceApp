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
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_number')->unique(); // COMP-YYYY-XXXXX
            $table->foreignId('guest_id')->nullable(); // If linked to specific guest (PMS integration)
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('room_number')->nullable();
            $table->foreignId('reported_by')->constrained('users')->onDelete('restrict'); // Front desk staff or guest
            $table->string('source')->default('front_desk'); // front_desk, phone, qr_code, pms_integration, app
            $table->string('category'); // plumbing, electrical, hvac, furniture, appliance, noise, other
            $table->string('subcategory')->nullable();
            $table->text('description');
            $table->string('priority')->default('medium'); // critical, high, medium, low
            
            // Status tracking
            $table->string('status')->default('open'); // open, acknowledged, in_progress, resolved, closed, cancelled
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            
            // Timing
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            // SLA tracking
            $table->integer('sla_minutes')->default(120); // Default 2 hours
            $table->timestamp('sla_deadline')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->timestamp('sla_breached_at')->nullable();
            
            // Resolution
            $table->text('resolution_notes')->nullable();
            $table->integer('resolution_time_minutes')->nullable();
            
            // Guest satisfaction
            $table->integer('satisfaction_score')->nullable(); // 1-5 rating
            $table->text('satisfaction_comments')->nullable();
            $table->timestamp('follow_up_sent_at')->nullable();
            
            // Compensation
            $table->boolean('compensation_required')->default(false);
            $table->string('compensation_type')->nullable(); // discount, upgrade, amenity, voucher
            $table->decimal('compensation_value', 10, 2)->nullable();
            $table->text('compensation_notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Escalation
            $table->boolean('is_escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->foreignId('escalated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('escalation_reason')->nullable();
            
            $table->timestamps();

            $table->index(['status', 'priority']);
            $table->index(['room_number', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index('complaint_number');
            $table->index('sla_deadline');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
