<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Salle;
use App\Models\Reservation;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Bouncer;

class SalleTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $salarie;

    public function setUp(): void
    {
        parent::setUp();

        // Utiliser la factory pour créer les utilisateurs
        $this->admin = User::factory()->create();

        $this->salarie = User::factory()->create();

        // Assigner les rôles avec Bouncer
        Bouncer::assign('admin')->to($this->admin);
        Bouncer::assign('salarie')->to($this->salarie);
    }

    /** @test */
    public function un_utilisateur_non_connecte_ne_peut_pas_acceder_aux_salles()
    {
        $response = $this->get(route('salles.index'));
        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function un_utilisateur_connecte_peut_voir_la_liste_des_salles()
    {
        Salle::create([
            'nom' => 'Salle A',
            'capacite' => 10,
            'surface' => 25.5,
        ]);

        Salle::create([
            'nom' => 'Salle B',
            'capacite' => 20,
            'surface' => 40.0,
        ]);

        $response = $this->actingAs($this->salarie)->get(route('salles.index'));

        $response->assertStatus(200);
        $response->assertViewHas('salles');

        $salles = $response->viewData('salles');
        $this->assertEquals(2, $salles->count());
    }

    /** @test */
    public function un_admin_voit_un_message_sil_ny_a_pas_de_salles()
    {
        // Aucune salle n'est créée

        $response = $this->actingAs($this->admin)->get(route('salles.index'));

        $response->assertStatus(200);
        $response->assertViewHas('salles');
        $response->assertSessionHas('message', 'Aucune salle trouvée. Veuillez en créer une.');
    }

    /** @test */
    public function un_admin_peut_acceder_au_formulaire_de_creation_de_salle()
    {
        $response = $this->actingAs($this->admin)->get(route('salles.create'));
        $response->assertStatus(200);
    }

    /** @test */
    public function un_salarie_ne_peut_pas_acceder_au_formulaire_de_creation_de_salle()
    {
        $response = $this->actingAs($this->salarie)->get(route('salles.create'));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('message', 'Vous n\'avez pas accès à cette page.');
    }

    /** @test */
    public function un_admin_peut_creer_une_salle()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('salles.store'), [
                'nom' => 'Nouvelle Salle',
                'capacite' => 15,
                'surface' => 30.5,
            ]);

        $response->assertRedirect(route('salles.index'));
        $response->assertSessionHas('success', 'Salle créée avec succès.');

        $this->assertDatabaseHas('salles', [
            'nom' => 'Nouvelle Salle',
            'capacite' => 15,
            'surface' => 30.5,
        ]);
    }

    /** @test */
    public function un_salarie_ne_peut_pas_creer_une_salle()
    {
        $response = $this->actingAs($this->salarie)
            ->post(route('salles.store'), [
                'nom' => 'Nouvelle Salle',
                'capacite' => 15,
                'surface' => 30.5,
            ]);

        // Comme le middleware 'auth' est appliqué mais pas de vérification spécifique dans le contrôleur
        // la requête passe mais le salarie n'a pas accès à l'interface de création
        // Dans une application réelle, il faudrait ajouter un middleware pour vérifier les autorisations

        $this->assertDatabaseMissing('salles', [
            'nom' => 'Nouvelle Salle',
        ]);
    }

    /** @test */
    public function un_admin_peut_acceder_au_formulaire_de_modification_de_salle()
    {
        $salle = Salle::create([
            'nom' => 'Salle à modifier',
            'capacite' => 10,
            'surface' => 25.5,
        ]);

        $response = $this->actingAs($this->admin)->get(route('salles.edit', ['id' => $salle->id]));
        $response->assertStatus(200);
        $response->assertViewHas('salle');
    }

    /** @test */
    public function un_salarie_ne_peut_pas_acceder_au_formulaire_de_modification_de_salle()
    {
        $salle = Salle::create([
            'nom' => 'Salle à modifier',
            'capacite' => 10,
            'surface' => 25.5,
        ]);

        $response = $this->actingAs($this->salarie)->get(route('salles.edit', ['id' => $salle->id]));
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('message', 'Vous n\'avez pas accès à cette page.');
    }

    /** @test */
    public function un_admin_peut_modifier_une_salle()
    {
        $salle = Salle::create([
            'nom' => 'Ancienne Salle',
            'capacite' => 10,
            'surface' => 25.5,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('salles.update', $salle->id), [
                'nom' => 'Salle Modifiée',
                'capacite' => 15,
                'surface' => 30.5,
            ]);

        $response->assertRedirect(route('salles.index'));
        $response->assertSessionHas('success', 'Salle modifiée avec succès.');

        $this->assertDatabaseHas('salles', [
            'id' => $salle->id,
            'nom' => 'Salle Modifiée',
            'capacite' => 15,
            'surface' => 30.5,
        ]);
    }

    /** @test */
    public function un_admin_peut_supprimer_une_salle()
    {
        $salle = Salle::create([
            'nom' => 'Salle à supprimer',
            'capacite' => 10,
            'surface' => 25.5,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('salles.destroy', $salle->id));

        $response->assertRedirect(route('salles.index'));
        $response->assertSessionHas('success', 'Salle supprimée avec succès.');

        $this->assertDatabaseMissing('salles', [
            'id' => $salle->id,
        ]);
    }

    /** @test */
    public function les_donnees_de_salle_sont_validees_lors_de_la_creation()
    {
        $response = $this->actingAs($this->admin)
            ->post(route('salles.store'), [
                'nom' => '', // Nom vide, devrait échouer
                'capacite' => 0, // Capacité invalide
                'surface' => -5, // Surface négative
            ]);

        $response->assertSessionHasErrors(['nom', 'capacite', 'surface']);

        $this->assertEquals(0, Salle::count());
    }

    /** @test */
    public function les_donnees_de_salle_sont_validees_lors_de_la_modification()
    {
        $salle = Salle::create([
            'nom' => 'Salle Test',
            'capacite' => 10,
            'surface' => 25.5,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('salles.update', $salle->id), [
                'nom' => '', // Nom vide, devrait échouer
                'capacite' => 0, // Capacité invalide
                'surface' => -5, // Surface négative
            ]);

        $response->assertSessionHasErrors(['nom', 'capacite', 'surface']);

        // Vérifier que la salle n'a pas été modifiée
        $this->assertDatabaseHas('salles', [
            'id' => $salle->id,
            'nom' => 'Salle Test',
            'capacite' => 10,
            'surface' => 25.5,
        ]);
    }
}
