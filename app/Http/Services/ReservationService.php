<?php

namespace App\Http\Services;

use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class ReservationService
{
    /**
     * Récupère toutes les réservations passées OU annulées (softDeleted)
     *
     * @return Collection<int, Reservation>
     */
    public function getAllCanceledReservations(): Collection
    {
        return Reservation::with(['salle', 'user'])
            ->where(function ($query) {
                $query->where('heure_fin', '<', Carbon::now())
                    ->orWhereNotNull('deleted_at');
            })
            ->orderBy('heure_debut', 'asc')
            ->withTrashed()
            ->get();
    }

    /**
     * Récupère toutes les réservations actives qui ne sont pas encore passées
     *
     * @return Collection<int, Reservation>
     */
    public function getAllReservations(): Collection
    {
        return Reservation::with(['salle', 'user'])
            ->where('heure_fin', '>=', Carbon::now())
            ->orderBy('heure_debut', 'asc')
            ->get();
    }

    /**
     * Récupère les réservations à venir d'un utilisateur spécifique
     *
     * @param int $userId L'ID de l'utilisateur
     * @return Collection<int, Reservation>
     */
    public function getUserUpcomingReservations(int $userId): Collection
    {
        return Reservation::with(['salle', 'user'])
            ->where('user_id', $userId)
            ->where('heure_fin', '>=', Carbon::now())
            ->orderBy('heure_debut', 'asc')
            ->get();
    }

    /**
     * Récupère les réservations passées ou annulées d'un utilisateur spécifique
     *
     * @param int $userId L'ID de l'utilisateur
     * @return Collection<int, Reservation>
     */
    public function getUserCanceledReservations(int $userId): Collection
    {
        return Reservation::with(['salle', 'user'])
            ->where('user_id', $userId)
            ->where(function ($query) {
                $query->where('heure_fin', '<', Carbon::now())
                    ->orWhereNotNull('deleted_at');
            })
            ->orderBy('heure_debut', 'asc')
            ->withTrashed()
            ->get();
    }

    /**
     * Créer une nouvelle réservation
     *
     * @param array<string, mixed> $data
     * @return Reservation
     */
    public function createReservation(array $data): Reservation
    {
        return Reservation::create($data);
    }

    /**
     * Mettre à jour une réservation existante
     *
     * @param Reservation $reservation
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateReservation(Reservation $reservation, array $data): bool
    {
        return $reservation->update($data);
    }

    /**
     * Vérifier la disponibilité d'une salle pour une période donnée
     *
     * @param int $salleId
     * @param string $heureDebut
     * @param string $heureFin
     * @param int|null $excludeReservationId
     * @return bool
     */
    public function isSalleAvailable(int $salleId, string $heureDebut, string $heureFin, ?int $excludeReservationId = null): bool
    {
        // Convertir les formats de date si nécessaire
        $debut = Carbon::parse($heureDebut);
        $fin = Carbon::parse($heureFin);

        $query = Reservation::where('salle_id', $salleId)
            ->where(function ($q) use ($debut, $fin) {
                // Vérifie si une réservation existe qui chevauche la période demandée
                $q->where(function ($query) use ($debut, $fin) {
                    $query->where('heure_debut', '<', $fin)
                        ->where('heure_fin', '>', $debut);
                });
            });

        // Exclure la réservation actuelle lors d'une mise à jour
        if ($excludeReservationId) {
            $query->where('id', '!=', $excludeReservationId);
        }

        // Si aucune réservation n'est trouvée qui chevauche la période, alors la salle est disponible
        return $query->count() === 0;
    }
}
