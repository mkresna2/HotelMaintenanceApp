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
        Schema::create('work_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // open, in_progress, pending_parts, resolved, closed, cancelled
            $table->string('display_name');
            $table->string('color')->default('#6c757d'); // For UI display
            $table->integer('sort_order')->default(0);
            $table->boolean('is_final')->default(false); // Whether this is a terminal status
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('wo_number')->unique(); // Auto-generated WO-YYYY-XXXXX
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            
            // Type and Priority
            $table->string('type')->default('corrective'); // corrective, preventive, emergency, inspection
            $table->string('priority')->default('medium'); // critical, high, medium, low
            $table->foreignId('status_id')->constrained('work_order_statuses')->onDelete('restrict');
            
            // Location/Asset linkage
            $table->string('location_type')->nullable(); // room, floor, building, outdoor, general
            $table->string('location_identifier')->nullable(); // Room number, etc.
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            
            // Guest complaint linkage
            $table->foreignId('complaint_id')->nullable()->constrained()->nullOnDelete();
            
            // Timing
            $table->timestamp('due_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            
            // SLA tracking
            $table->integer('sla_minutes')->nullable(); // Expected resolution time based on priority
            $table->timestamp('sla_deadline')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->timestamp('sla_breached_at')->nullable();
            
            // Work details
            $table->text('problem_description')->nullable();
            $table->text('work_performed')->nullable();
            $table->json('parts_used')->nullable(); // [{part_id, quantity, cost}]
            $table->decimal('labor_hours', 8, 2)->default(0);
            $table->decimal('labor_cost', 10, 2)->default(0);
            $table->decimal('parts_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            
            // Additional info
            $table->json('checklist_results')->nullable(); // For PM tasks
            $table->text('notes')->nullable();
            $table->integer('attachment_count')->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('parent_wo_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->integer('recurrence_count')->default(0);
            
            // Guest impact
            $table->boolean('affects_guest')->default(false);
            $table->string('affected_room')->nullable();
            $table->boolean('guest_notified')->default(false);
            
            $table->timestamps();

            $table->index(['type', 'priority', 'status_id']);
            $table->index(['assigned_to', 'status_id']);
            $table->index(['asset_id', 'created_at']);
            $table->index(['location_type', 'location_identifier']);
            $table->index('sla_deadline');
            $table->index('due_date');
            $table->index('wo_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('work_order_statuses');
    }
};
