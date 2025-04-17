<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Salle;
use App\Models\Reservation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WelcomeController extends Controller
{
    /**
     * @return View
     */
    public function index(): View
    {
        /**
         * @var User
         */
        $user = Auth::user();

        if ($user->isA('salarie')) {
            // Pour les salariés, pas besoin de statistiques
            return view('dashboard');
        } else {
            // Pour les admins, on prépare les statistiques
            $stats = [
                'totalSalles' => Salle::count(),
                'totalReservations' => Reservation::count(),
                'reservationsThisMois' => Reservation::whereMonth('heure_debut', now()->month)->count(),
                'mostUsedSalle' => $this->getMostUsedSalle(),
                'averageDuration' => $this->getAverageDuration(),
                'roomDistribution' => $this->getRoomDistribution(),
                'monthlyEvolution' => $this->getMonthlyEvolution(),
                'topUsers' => $this->getTopUsers(),
                'weekdayDistribution' => $this->getWeekdayDistribution()
            ];

            return view('dashboard', [
                'stats' => $stats
            ]);
        }
    }

    /**
     * Obtenir la répartition des réservations par jour de la semaine
     *
     * @return array<int, int>
     */
    private function getWeekdayDistribution(): array
    {
        $weekdays = [0, 0, 0, 0, 0, 0, 0]; // Lundi à Dimanche

        $reservations = Reservation::get();

        foreach ($reservations as $reservation) {
            $dayOfWeek = Carbon::parse($reservation->heure_debut)->dayOfWeekIso; // 1 (Lundi) à 7 (Dimanche)
            $weekdays[$dayOfWeek - 1]++; // Ajuster pour l'index 0-6
        }

        return $weekdays;
    }

    /**
     * Obtenir la salle la plus utilisée
     *
     * @return Salle|null
     */
    private function getMostUsedSalle(): ?Salle
    {
        return Salle::withCount('reservations')
            ->orderBy('reservations_count', 'desc')
            ->first();
    }

    /**
     * Obtenir la distribution des réservations par salle
     *
     * @return array<string, mixed>
     */
    private function getRoomDistribution(): array
    {
        $salles = Salle::withCount('reservations')
            ->orderBy('reservations_count', 'desc')
            ->get();

        $labels = $salles->pluck('nom')->toArray();
        $data = $salles->pluck('reservations_count')->toArray();
        $backgroundColors = $this->generateChartColors(count($salles));

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $data,
                    'backgroundColor' => $backgroundColors
                ]
            ]
        ];
    }

    /**
     * Obtenir l'évolution mensuelle des réservations
     *
     * @return array<string, mixed>
     */
    private function getMonthlyEvolution(): array
    {
        $months = collect();

        // Récupérer les 6 derniers mois
        for ($i = 5; $i >= 0; $i--) {
            $months->push(Carbon::now()->subMonths($i)->format('Y-m'));
        }

        $monthLabels = $months->map(function ($yearMonth) {
            return Carbon::parse($yearMonth . '-01')->format('M Y');
        })->toArray();

        $reservationCounts = $months->map(function ($yearMonth) {
            list($year, $month) = explode('-', $yearMonth);
            return Reservation::whereYear('heure_debut', $year)
                ->whereMonth('heure_debut', $month)
                ->count();
        })->toArray();

        return [
            'labels' => $monthLabels,
            'datasets' => [
                [
                    'label' => 'Nombre de réservations',
                    'data' => $reservationCounts,
                    'fill' => false,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'tension' => 0.1
                ]
            ]
        ];
    }

    /**
     * Obtenir les utilisateurs qui font le plus de réservations
     *
     * @return \Illuminate\Support\Collection<int, \stdClass>
     */
    private function getTopUsers(): \Illuminate\Support\Collection
    {
        return DB::table('reservations')
            ->join('users', 'reservations.user_id', '=', 'users.id')
            ->select('users.last_name', DB::raw('count(*) as total'))
            ->groupBy('users.id', 'users.last_name', 'users.first_name')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Calculer la durée moyenne des réservations
     *
     * @return array<string, mixed>
     */
    private function getAverageDuration(): array
    {
        // Debugging
        \Log::info('Début calcul durée moyenne');

        $reservations = Reservation::get();

        if ($reservations->isEmpty()) {
            \Log::info('Aucune réservation trouvée');
            return [
                'minutes' => 0,
                'formatted' => '0h 00min'
            ];
        }

        \Log::info("Nombre de réservations trouvées: " . $reservations->count());

        $totalMinutes = 0;
        $count = 0;

        foreach ($reservations as $reservation) {
            try {
                $debut = Carbon::parse($reservation->heure_debut);
                $fin = Carbon::parse($reservation->heure_fin);

                \Log::info("Reservation #{$reservation->id}: Début = {$debut->format('Y-m-d H:i:s')}, Fin = {$fin->format('Y-m-d H:i:s')}");

                // Utiliser diffInMinutes avec le paramètre absolu à true
                $dureeMinutes = $debut->diffInMinutes($fin, false);

                \Log::info("Durée calculée: $dureeMinutes minutes");

                if ($dureeMinutes > 0) {
                    $totalMinutes += $dureeMinutes;
                    $count++;
                } else {
                    \Log::warning("Durée négative ou nulle ignorée: $dureeMinutes minutes");
                }
            } catch (\Exception $e) {
                \Log::error("Erreur dans le calcul de la durée: " . $e->getMessage());
            }
        }

        \Log::info("Total minutes: $totalMinutes, Nombre de réservations valides: $count");

        // Éviter la division par zéro
        if ($count == 0) {
            \Log::info('Aucune réservation valide - Durée moyenne: 0');
            return [
                'minutes' => 0,
                'formatted' => '0h 00min'
            ];
        }

        // Convertir en heures et minutes
        $avgMinutes = $totalMinutes / $count;
        $hours = floor($avgMinutes / 60);
        $minutes = round($avgMinutes % 60);

        $formatted = sprintf('%dh %02dmin', $hours, $minutes);
        \Log::info("Durée moyenne calculée: $avgMinutes minutes ($formatted)");

        return [
            'minutes' => round($avgMinutes),
            'formatted' => $formatted
        ];
    }

    /**
     * Générer des couleurs pour les graphiques
     *
     * @param int $count
     * @return array<int, string>
     */
    private function generateChartColors(int $count): array
    {
        $baseColors = [
            'rgba(75, 192, 192, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(255, 99, 132, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)'
        ];

        $colors = [];
        for ($i = 0; $i < $count; $i++) {
            $colors[] = $baseColors[$i % count($baseColors)];
        }

        return $colors;
    }
}
