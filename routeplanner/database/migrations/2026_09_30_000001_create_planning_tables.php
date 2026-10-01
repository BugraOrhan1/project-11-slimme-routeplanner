<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabellen volgens Project11_database_schema.mermaid (bijgewerkt na gesprek 23-09-2026).
 * Code en tabelnamen zijn Engels (PID CS-01), de interface is Nederlands.
 */
return new class extends Migration
{
    public function up(): void
    {
        // KLANT – maatschappijen zoals Shell, BP en Total
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('color', 7)->default('#003D52');
            $table->timestamps();
        });

        // LOCATIE – tankstation
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('address');
            $table->string('postal_code', 10)->nullable();
            $table->string('city');
            $table->string('region')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->time('open_from')->nullable();
            $table->time('open_until')->nullable();
            $table->text('access_notes')->nullable();
            $table->timestamps();
        });

        // TANKINSTALLATIE – diesel, benzine, Euro 98, ...
        Schema::create('tanks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('product');
            $table->string('kind')->default('ondergronds');
            $table->unsignedInteger('capacity_liters')->nullable();
            $table->date('last_inspection')->nullable();
            $table->timestamps();
        });

        // CERTIFICAAT – diploma's voor geaccrediteerd werk
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // ACTIVITEIT – werkzaamheid met vaste tijd per type
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('duration_minutes');
            $table->boolean('per_tank')->default(false);
            $table->foreignId('certificate_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // INSPECTEUR
        Schema::create('inspectors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('start_address');
            $table->decimal('start_latitude', 10, 7);
            $table->decimal('start_longitude', 10, 7);
            $table->time('work_start')->default('07:30');
            $table->time('work_end')->default('16:30');
            $table->string('color', 7)->default('#003D52');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('certificate_inspector', function (Blueprint $table) {
            $table->foreignId('inspector_id')->constrained()->cascadeOnDelete();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->date('valid_until')->nullable();
            $table->primary(['inspector_id', 'certificate_id']);
        });

        // OPDRACHT – één bezoek aan een station
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('open'); // open, ingepland, uitgevoerd, geannuleerd
            $table->date('plan_month')->nullable(); // maandblok van de klant
            $table->date('deadline');               // opgelegd door de klant
            $table->unsignedTinyInteger('priority')->default(2); // 1 hoog, 2 normaal, 3 laag
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_assignment', function (Blueprint $table) {
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->primary(['assignment_id', 'activity_id']);
        });

        // ROUTE – één inspecteur, één dag
        Schema::create('routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspector_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status')->default('voorstel'); // voorstel, goedgekeurd
            $table->decimal('total_km', 8, 1)->default(0);
            $table->unsignedInteger('total_drive_minutes')->default(0);
            $table->unsignedInteger('total_work_minutes')->default(0);
            $table->time('end_time')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('route_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('route_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->decimal('distance_km', 8, 1)->default(0);
            $table->unsignedInteger('drive_minutes')->default(0);
            $table->time('planned_arrival')->nullable();
            $table->time('planned_departure')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        // WIJZIGINGSLOG – wie heeft wat aangepast
        Schema::create('change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action'); // aangemaakt, gewijzigd, verwijderd, ...
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // NOTIFICATIE – deadline verloopt bijna (planner + bedrijfsleider)
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assignment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('message');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'assignment_id']);
        });
    }

    public function down(): void
    {
        foreach (['alerts', 'change_logs', 'route_stops', 'routes', 'activity_assignment', 'assignments',
            'certificate_inspector', 'inspectors', 'activities', 'certificates', 'tanks', 'locations', 'customers'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
