<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Salle;
use App\Models\Reservation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Bouncer;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReservationCreated;
use App\Mail\ReservationCanceled;

class ReservationTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $salarie;
    protected $salle;

    public function setUp(): void
    {
        parent::setUp();

        // Désactiver l'envoi réel des mails
        Mail::fake();

        // Utiliser la factory pour créer les utilisateurs
        $this->admin = User::factory()->create();

        $this->salarie = User::factory()->create();

        // Assigner les rôles avec Bouncer
        Bouncer::assign('admin')->to($this->admin);
        Bouncer::assign('salarie')->to($this->salarie);

        // Créer une salle directement
        $this->salle = Salle::create([
            'nom' => 'Salle de réunion',
            'capacite' => 10,
            'surface' => 25.5,
        ]);
    }

    /** @test */
    public function un_utilisateur_non_connecte_ne_peut_pas_acceder_aux_reservations()
    {
        $response = $this->get(route('reservations.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function un_admin_peut_voir_toutes_les_reservations()
    {
        // Créer quelques réservations pour les deux types d'utilisateurs
        $reservationAdmin = Reservation::create([
            'user_id' => $this->admin->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHour(),
            'heure_fin' => Carbon::now()->addHours(2),
        ]);

        $reservationSalarie = Reservation::create([
            'user_id' => $this->salarie->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHours(3),
            'heure_fin' => Carbon::now()->addHours(4),
        ]);

        $response = $this->actingAs($this->admin)->get(route('reservations.index'));
        $response->assertStatus(200);
        $response->assertViewHas('reservations');

        // Vérifier que les deux réservations sont dans la vue
        $reservations = $response->viewData('reservations');
        $this->assertEquals(2, $reservations->count());
    }

    /** @test */
    public function un_salarie_ne_voit_que_ses_propres_reservations()
    {
        // Créer quelques réservations pour les deux types d'utilisateurs
        $reservationAdmin = Reservation::create([
            'user_id' => $this->admin->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHour(),
            'heure_fin' => Carbon::now()->addHours(2),
        ]);

        $reservationSalarie = Reservation::create([
            'user_id' => $this->salarie->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHours(3),
            'heure_fin' => Carbon::now()->addHours(4),
        ]);

        $response = $this->actingAs($this->salarie)->get(route('reservations.index'));
        $response->assertStatus(200);
        $response->assertViewHas('reservations');

        // Vérifier que seule la réservation du salarié est dans la vue
        $reservations = $response->viewData('reservations');
        $this->assertEquals(1, $reservations->count());
        $this->assertEquals($this->salarie->id, $reservations->first()->user_id);
    }

    /** @test */
    public function un_utilisateur_peut_creer_une_reservation()
    {
        $heureDebut = Carbon::now()->addDay()->setHour(10)->setMinute(0);
        $heureFin = Carbon::now()->addDay()->setHour(11)->setMinute(0);

        $response = $this->actingAs($this->salarie)
            ->post(route('reservations.store'), [
                'salle_id' => $this->salle->id,
                'heure_debut' => $heureDebut->format('Y-m-d\TH:i'),
                'heure_fin' => $heureFin->format('Y-m-d\TH:i'),
            ]);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'user_id' => $this->salarie->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
        ]);

        // Vérifier que l'email a été envoyé
        Mail::assertSent(ReservationCreated::class);
    }

    /** @test */
    public function une_reservation_ne_peut_pas_etre_creee_si_la_salle_est_occupee()
    {
        // Créer d'abord une réservation
        $existingReservation = Reservation::create([
            'user_id' => $this->admin->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::tomorrow()->setHour(10)->setMinute(0),
            'heure_fin' => Carbon::tomorrow()->setHour(12)->setMinute(0),
        ]);

        // Essayer de créer une réservation qui chevauche
        $response = $this->actingAs($this->salarie)
            ->post(route('reservations.store'), [
                'salle_id' => $this->salle->id,
                'heure_debut' => Carbon::tomorrow()->setHour(11)->setMinute(0)->format('Y-m-d\TH:i'),
                'heure_fin' => Carbon::tomorrow()->setHour(13)->setMinute(0)->format('Y-m-d\TH:i'),
            ]);

        $response->assertSessionHasErrors();
        $this->assertEquals(1, Reservation::count()); // Seule la première réservation existe
    }

    /** @test */
    public function un_utilisateur_peut_modifier_sa_propre_reservation()
    {
        $reservation = Reservation::create([
            'user_id' => $this->salarie->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHour(),
            'heure_fin' => Carbon::now()->addHours(2),
        ]);

        $newHeureDebut = Carbon::now()->addDays(2)->setHour(14)->setMinute(0);
        $newHeureFin = Carbon::now()->addDays(2)->setHour(16)->setMinute(0);

        $response = $this->actingAs($this->salarie)
            ->put(route('reservations.update', $reservation->id), [
                'salle_id' => $this->salle->id,
                'heure_debut' => $newHeureDebut->format('Y-m-d\TH:i'),
                'heure_fin' => $newHeureFin->format('Y-m-d\TH:i'),
            ]);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'heure_debut' => $newHeureDebut,
            'heure_fin' => $newHeureFin,
        ]);
    }

    /** @test */
    public function un_salarie_ne_peut_pas_modifier_la_reservation_dun_autre_utilisateur()
    {
        // Utiliser la factory pour un autre utilisateur
        $otherUser = User::factory()->create([
            'name' => 'Another Employee',
            'email' => 'another@example.com',
        ]);

        Bouncer::assign('salarie')->to($otherUser);

        $reservation = Reservation::create([
            'user_id' => $otherUser->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHour(),
            'heure_fin' => Carbon::now()->addHours(2),
        ]);

        $response = $this->actingAs($this->salarie)
            ->put(route('reservations.update', $reservation->id), [
                'salle_id' => $this->salle->id,
                'heure_debut' => Carbon::now()->addDays(2)->format('Y-m-d\TH:i'),
                'heure_fin' => Carbon::now()->addDays(2)->addHours(2)->format('Y-m-d\TH:i'),
            ]);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'heure_debut' => Carbon::now()->addHour(),
        ]);
    }

    /** @test */
    public function un_admin_peut_modifier_nimporte_quelle_reservation()
    {
        $reservation = Reservation::create([
            'user_id' => $this->salarie->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHour(),
            'heure_fin' => Carbon::now()->addHours(2),
        ]);

        $newHeureDebut = Carbon::now()->addDays(2)->setHour(14)->setMinute(0);
        $newHeureFin = Carbon::now()->addDays(2)->setHour(16)->setMinute(0);

        $response = $this->actingAs($this->admin)
            ->put(route('reservations.update', $reservation->id), [
                'salle_id' => $this->salle->id,
                'heure_debut' => $newHeureDebut->format('Y-m-d\TH:i'),
                'heure_fin' => $newHeureFin->format('Y-m-d\TH:i'),
            ]);

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'heure_debut' => $newHeureDebut,
            'heure_fin' => $newHeureFin,
        ]);
    }

    /** @test */
    public function un_utilisateur_peut_annuler_sa_propre_reservation()
    {
        $reservation = Reservation::create([
            'user_id' => $this->salarie->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHour(),
            'heure_fin' => Carbon::now()->addHours(2),
        ]);

        $response = $this->actingAs($this->salarie)
            ->delete(route('reservations.destroy', $reservation->id));

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);

        // Vérifier que l'email d'annulation a été envoyé
        Mail::assertSent(ReservationCanceled::class);
    }

    /** @test */
    public function un_salarie_ne_peut_pas_annuler_la_reservation_dun_autre_utilisateur()
    {
        // Utiliser la factory pour un autre utilisateur
        $otherUser = User::factory()->create([
            'name' => 'Another Employee',
            'email' => 'another@example.com',
        ]);

        Bouncer::assign('salarie')->to($otherUser);

        $reservation = Reservation::create([
            'user_id' => $otherUser->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHour(),
            'heure_fin' => Carbon::now()->addHours(2),
        ]);

        $response = $this->actingAs($this->salarie)
            ->delete(route('reservations.destroy', $reservation->id));

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
    }

    /** @test */
    public function un_admin_peut_annuler_nimporte_quelle_reservation()
    {
        $reservation = Reservation::create([
            'user_id' => $this->salarie->id,
            'salle_id' => $this->salle->id,
            'heure_debut' => Carbon::now()->addHour(),
            'heure_fin' => Carbon::now()->addHours(2),
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('reservations.destroy', $reservation->id));

        $response->assertRedirect(route('reservations.index'));
        $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);

        // Vérifier que l'email d'annulation a été envoyé
        Mail::assertSent(ReservationCanceled::class);
    }

    /** @test */
    public function un_utilisateur_peut_acceder_au_formulaire_de_creation_de_reservation()
    {
        $response = $this->actingAs($this->salarie)->get(route('reservations.create'));
        $response->assertStatus(200);
        $response->assertViewHas('salles');
    }

    /** @test */
    public function un_utilisateur_est_redirige_sil_ny_a_pas_de_salles_disponibles()
    {
        // Supprimer toutes les salles
        Salle::query()->delete();

        $response = $this->actingAs($this->salarie)->get(route('reservations.create'));
        $response->assertRedirect(route('reservations.index'));
        $response->assertSessionHas('message', 'Aucune salle disponible pour réservation.');
    }
}
