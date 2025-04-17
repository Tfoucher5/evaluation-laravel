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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salle_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->datetime('heure_debut');
            $table->datetime('heure_fin');
            $table->softDeletes();
            $table->timestamps();
        });

        Bouncer::allow('admin')->to('reservation-create');
        Bouncer::allow('admin')->to('reservation-update');
        Bouncer::allow('admin')->to('reservation-delete');
        Bouncer::allow('admin')->to('reservation-edit');
        Bouncer::allow('salarie')->to('reservation-create');
        Bouncer::allow('salarie')->to('reservation-update');
        Bouncer::allow('salarie')->to('reservation-delete');
        Bouncer::Refresh();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
