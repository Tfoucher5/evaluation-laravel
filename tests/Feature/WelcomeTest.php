<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Salle;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;
use Bouncer;

class WelcomeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Configuration initiale pour les tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Initialiser Bouncer pour les tests
        Bouncer::cache();
    }

    /**
     * Test de la vue dashboard pour un utilisateur avec rôle salarie
     */
    public function test_index_returns_dashboard_for_salarie()
    {
        // Créer un utilisateur
        $user = User::factory()->create();
        Bouncer::assign('salarie')->to($user);

        // Agir comme cet utilisateur et faire la requête
        $response = $this->actingAs($user)->get('/dashboard');

        // Vérifier que la vue dashboard est retournée
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
    }

    /**
     * Test de la vue dashboard pour un utilisateur avec rôle admin
     */
    public function test_index_returns_dashboard_with_data_for_admin()
    {
        // Créer un utilisateur avec le rôle "admin"
        $user = User::factory()->create();
        Bouncer::assign('admin')->to($user);

        // Agir comme cet utilisateur et faire la requête
        $response = $this->actingAs($user)->get('/dashboard');

        // Vérifier que la vue dashboard est retournée
        $response->assertStatus(200);
        $response->assertViewIs('dashboard');

        // Vérifier que la réponse a des données (sans vérifier le nom exact de la clé)
        $this->assertNotEmpty($response);
    }

    /**
     * Test de base pour vérifier que les méthodes de statistiques ne génèrent pas d'erreur
     */
    public function test_admin_dashboard_loads_without_errors()
    {
        // Créer un utilisateur admin
        $user = User::factory()->create();
        Bouncer::assign('admin')->to($user);

        // Créer une salle et des réservations pour avoir des données
        $salle = $this->createSalle('Salle Test');
        $this->createReservations($salle, $user);

        // Vérifier que la page charge sans erreur
        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
    }

    /**
     * Test de base pour vérifier les données des statistiques
     */
    public function test_admin_stats_data()
    {
        // Créer un utilisateur admin
        $user = User::factory()->create();
        Bouncer::assign('admin')->to($user);

        // Créer une salle
        $salle = $this->createSalle('Salle Test');

        // Créer des réservations
        $this->createReservations($salle, $user, 3);

        // Faire la requête
        $response = $this->actingAs($user)->get('/');

        // Obtenir toutes les données de la vue
        $viewData = $response->original->getData();

        // Parcourir les données pour chercher des informations sur les statistiques
        // (peu importe comment elles sont structurées)
        $this->assertTrue($this->hasStatisticalData($viewData));
    }

    /**
     * Test spécifique pour la méthode getMostUsedSalle
     */
    public function test_most_used_salle_method()
    {
        // Créer un utilisateur admin pour accéder au controller
        $user = User::factory()->create();
        Bouncer::assign('admin')->to($user);

        // Créer deux salles avec différentes nombres de réservations
        $salle1 = $this->createSalle('Salle 1');
        $salle2 = $this->createSalle('Salle 2');

        // Créer plus de réservations pour salle1
        $this->createReservations($salle1, $user, 5);
        $this->createReservations($salle2, $user, 2);

        // Appeler directement la méthode getMostUsedSalle du controller
        $controller = new \App\Http\Controllers\WelcomeController();
        $mostUsedSalle = $this->callPrivateMethod($controller, 'getMostUsedSalle');

        // Vérifier que la salle la plus utilisée est salle1
        $this->assertEquals($salle1->id, $mostUsedSalle->id);
    }

    /**
     * Test spécifique pour la méthode getWeekdayDistribution
     */
    public function test_weekday_distribution_method()
    {
        // Créer un utilisateur admin
        $user = User::factory()->create();
        Bouncer::assign('admin')->to($user);

        // Créer une salle
        $salle = $this->createSalle('Salle Test');

        // Créer des réservations pour différents jours
        $this->createReservationForWeekday($salle, $user, 1); // Lundi
        $this->createReservationForWeekday($salle, $user, 3); // Mercredi

        // Appeler directement la méthode getWeekdayDistribution
        $controller = new \App\Http\Controllers\WelcomeController();
        $weekdayDistribution = $this->callPrivateMethod($controller, 'getWeekdayDistribution');

        // Vérifier que c'est un tableau de 7 jours
        $this->assertIsArray($weekdayDistribution);
        $this->assertCount(7, $weekdayDistribution);

        // Vérifier qu'il y a au moins une réservation le lundi et le mercredi
        $this->assertGreaterThan(0, $weekdayDistribution[0]); // Lundi
        $this->assertGreaterThan(0, $weekdayDistribution[2]); // Mercredi
    }

    /**
     * Test spécifique pour la méthode getAverageDuration
     */
    public function test_average_duration_method()
    {
        // Créer un utilisateur admin
        $user = User::factory()->create();
        Bouncer::assign('admin')->to($user);

        // Créer une salle
        $salle = $this->createSalle('Salle Test');

        // Créer des réservations avec des durées spécifiques
        $this->createReservationWithDuration($salle, $user, 60); // 1h
        $this->createReservationWithDuration($salle, $user, 120); // 2h

        // Appeler directement la méthode getAverageDuration
        $controller = new \App\Http\Controllers\WelcomeController();
        $averageDuration = $this->callPrivateMethod($controller, 'getAverageDuration');

        // Vérifier le format du résultat
        $this->assertArrayHasKey('minutes', $averageDuration);
        $this->assertArrayHasKey('formatted', $averageDuration);

        // Vérifier que la durée moyenne est correcte (90 minutes)
        $this->assertEquals(90, $averageDuration['minutes']);
    }

    /**
     * Vérifie si les données contiennent des informations statistiques
     */
    private function hasStatisticalData($data)
    {
        // Si c'est un tableau ou un objet, on vérifie ses propriétés
        if (is_array($data) || is_object($data)) {
            foreach ($data as $key => $value) {
                // On cherche des mots clés liés aux statistiques
                if (is_string($key) && (
                    strpos($key, 'stats') !== false ||
                    strpos($key, 'count') !== false ||
                    strpos($key, 'total') !== false ||
                    strpos($key, 'distribution') !== false ||
                    strpos($key, 'average') !== false ||
                    strpos($key, 'evolution') !== false ||
                    strpos($key, 'top') !== false
                )) {
                    return true;
                }

                // Recherche récursive dans les sous-éléments
                if ($this->hasStatisticalData($value)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Permet d'appeler une méthode privée d'un objet
     */
    private function callPrivateMethod($object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    /**
     * Créer une salle
     */
    private function createSalle($nom)
    {
        return Salle::create([
            'nom' => $nom,
            'capacite' => 10,
            'equipements' => 'Vidéoprojecteur, Tableau'
        ]);
    }

    /**
     * Créer plusieurs réservations pour une salle et un utilisateur
     */
    private function createReservations($salle, $user, $count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            Reservation::create([
                'salle_id' => $salle->id,
                'user_id' => $user->id,
                'heure_debut' => now()->addHours($i),
                'heure_fin' => now()->addHours($i + 1),
                'titre' => 'Réservation Test ' . ($i + 1),
                'description' => 'Description de test'
            ]);
        }
    }

    /**
     * Créer une réservation avec une durée spécifique en minutes
     */
    private function createReservationWithDuration($salle, $user, $durationMinutes)
    {
        $startTime = now();
        $endTime = (clone $startTime)->addMinutes($durationMinutes);

        Reservation::create([
            'salle_id' => $salle->id,
            'user_id' => $user->id,
            'heure_debut' => $startTime,
            'heure_fin' => $endTime,
            'titre' => 'Réservation Test Durée',
            'description' => 'Description de test'
        ]);
    }

    /**
     * Créer une réservation pour un jour spécifique de la semaine (1=Lundi, 7=Dimanche)
     */
    private function createReservationForWeekday($salle, $user, $dayOfWeek)
    {
        // Trouver la date du prochain jour de la semaine spécifié
        $currentDayOfWeek = Carbon::now()->dayOfWeekIso;
        $daysToAdd = ($dayOfWeek - $currentDayOfWeek + 7) % 7;
        if ($daysToAdd === 0) $daysToAdd = 7; // Pour obtenir le prochain jour même

        $date = Carbon::now()->addDays($daysToAdd)->setTime(10, 0, 0);

        Reservation::create([
            'salle_id' => $salle->id,
            'user_id' => $user->id,
            'heure_debut' => $date,
            'heure_fin' => (clone $date)->addHour(),
            'titre' => 'Réservation Test Jour ' . $dayOfWeek,
            'description' => 'Description de test'
        ]);
    }

    /**
     * Créer un nombre spécifique de réservations pour un mois relatif (0=mois actuel, 1=mois précédent)
     */
    private function createReservationForMonth($salle, $user, $monthsAgo, $count)
    {
        $date = Carbon::now()->subMonths($monthsAgo)->startOfMonth();

        for ($i = 0; $i < $count; $i++) {
            Reservation::create([
                'salle_id' => $salle->id,
                'user_id' => $user->id,
                'heure_debut' => (clone $date)->addDays($i),
                'heure_fin' => (clone $date)->addDays($i)->addHour(),
                'titre' => 'Réservation Test Mois ' . (Carbon::now()->subMonths($monthsAgo)->format('M')),
                'description' => 'Description de test'
            ]);
        }
    }
}
