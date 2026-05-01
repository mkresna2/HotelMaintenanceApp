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
        Schema::create('maintenance_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('asset_categories')->nullOnDelete();
            $table->string('location_type')->nullable(); // room, floor, building
            $table->string('location_identifier')->nullable();
            
            // Schedule type
            $table->string('frequency_type')->default('monthly'); // daily, weekly, monthly, quarterly, annually, custom
            $table->integer('frequency_interval')->default(1); // Every X days/weeks/months
            $table->json('custom_schedule')->nullable(); // For complex schedules (e.g., specific days)
            
            // Timing
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->time('preferred_time')->nullable();
            $table->integer('estimated_duration_minutes')->default(60);
            $table->string('day_of_week')->nullable(); // For weekly schedules
            $table->integer('day_of_month')->nullable(); // For monthly schedules (1-31)
            $table->string('month_of_year')->nullable(); // For annual schedules
            
            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            
            // Task details
            $table->json('checklist')->nullable(); // Array of checklist items
            $table->text('instructions')->nullable();
            $table->json('required_parts')->nullable(); // Expected parts needed
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_generate_wo')->default(true); // Auto-create work order when due
            $table->integer('days_before_due')->default(1); // Generate WO X days before due
            
            // Tracking
            $table->timestamp('last_generated_at')->nullable();
            $table->timestamp('next_due_at')->nullable();
            $table->integer('total_occurrences')->default(0);
            $table->integer('completed_occurrences')->default(0);
            $table->integer('missed_occurrences')->default(0);
            
            $table->timestamps();

            $table->index(['frequency_type', 'is_active']);
            $table->index('next_due_at');
            $table->index(['asset_id', 'is_active']);
        });

        Schema::create('preventive_maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('task_number')->unique(); // PMT-YYYY-XXXXX
            $table->foreignId('schedule_id')->constrained('maintenance_schedules')->onDelete('cascade');
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('checklist_items')->nullable(); // [{item, required, completed}]
            $table->date('scheduled_date');
            $table->date('due_date');
            $table->timestamp('completed_at')->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, completed, overdue, skipped
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->text('findings')->nullable();
            $table->boolean('requires_followup')->default(false);
            $table->foreignId('followup_wo_id')->nullable()->constrained('work_orders')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index(['asset_id', 'scheduled_date']);
            $table->index('task_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('preventive_maintenance_tasks');
        Schema::dropIfExists('maintenance_schedules');
    }
};
