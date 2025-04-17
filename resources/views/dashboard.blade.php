<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-semibold fs-4 text-secondary">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="card shadow">
                <div class="card-body">
                    @if (auth()->user()->isA('salarie'))
                        <h3 class="fs-5 fw-bold mb-4">Bienvenue sur l'application de réservation de salles</h3>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="bi bi-calendar-check"></i> Mes prochaines
                                            réservations</h5>
                                        <p class="card-text">Consultez vos réservations.
                                        </p>
                                        <a href="{{ route('reservations.index') }}" class="btn btn-primary">
                                            Voir mes réservations
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card bg-light h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="bi bi-plus-square"></i> Nouvelle réservation
                                        </h5>
                                        <p class="card-text">Réservez rapidement une salle pour vos réunions et
                                            événements.</p>
                                        <a href="{{ route('reservations.create') }}" class="btn btn-success">
                                            Réserver une salle
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="card bg-light h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><i class="bi bi-building"></i> Salles disponibles</h5>
                                        <p class="card-text">Explorez les différentes salles et leurs équipements.</p>
                                        <a href="{{ route('salles.index') }}" class="btn btn-outline-primary">
                                            Voir les salles
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <h3 class="fs-5 fw-bold mb-4">Tableau de bord administrateur</h3>

                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total des salles</h5>
                                        <p class="card-text fs-2">{{ $stats['totalSalles'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total des réservations</h5>
                                        <p class="card-text fs-2">{{ $stats['totalReservations'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Réservations ce mois</h5>
                                        <p class="card-text fs-2">{{ $stats['reservationsThisMois'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Durée moyenne</h5>
                                        <p class="card-text fs-2">
                                            {{ isset($stats['averageDuration']['formatted']) ? $stats['averageDuration']['formatted'] : '0h 00min' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Salle la plus utilisée</h5>
                                        @if ($stats['mostUsedSalle'])
                                            <p class="card-text">
                                                <span class="fs-4">{{ $stats['mostUsedSalle']->nom }}</span><br>
                                                {{ $stats['mostUsedSalle']->reservations_count }} réservations<br>
                                                Capacité: {{ $stats['mostUsedSalle']->capacite }} personnes<br>
                                                Surface: {{ $stats['mostUsedSalle']->surface }} m²
                                            </p>
                                        @else
                                            <p class="card-text">Aucune réservation</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card bg-light h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">Réservations par jour de la semaine</h5>
                                        <canvas id="weekdayChart" width="400" height="200"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Répartition des réservations par salle</h5>
                                        <canvas id="reservationsChart" width="400" height="300"></canvas>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Évolution mensuelle des réservations</h5>
                                        <canvas id="monthlyChart" width="400" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Top 5 des utilisateurs</h5>
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Utilisateur</th>
                                                    <th class="text-end">Nombre de réservations</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($stats['topUsers'] as $user)
                                                    <tr>
                                                        <td>{{ $user->last_name }}</td>
                                                        <td class="text-end">{{ $user->total }}</td>
                                                    </tr>
                                                @endforeach
                                                @if (count($stats['topUsers']) == 0)
                                                    <tr>
                                                        <td colspan="2" class="text-center">Aucune donnée
                                                            disponible</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Script pour les graphiques (utilisant Chart.js) -->
                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                        @php
                            $weekdayData = $stats['weekdayDistribution'] ?? [10, 15, 20, 25, 18, 5, 2];
                        @endphp
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // Configuration du graphique de répartition des réservations
                                const roomData = @json($stats['roomDistribution']);
                                const ctx1 = document.getElementById('reservationsChart').getContext('2d');
                                new Chart(ctx1, {
                                    type: 'pie',
                                    data: roomData,
                                    options: {
                                        responsive: true,
                                        plugins: {
                                            legend: {
                                                position: 'right',
                                            },
                                            tooltip: {
                                                callbacks: {
                                                    label: function(context) {
                                                        let label = context.label || '';
                                                        let value = context.parsed || 0;
                                                        let total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                                        let percentage = Math.round((value / total) * 100);
                                                        return `${label}: ${value} (${percentage}%)`;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                });

                                // Configuration du graphique d'évolution mensuelle
                                const monthlyData = @json($stats['monthlyEvolution']);
                                const ctx2 = document.getElementById('monthlyChart').getContext('2d');
                                new Chart(ctx2, {
                                    type: 'line',
                                    data: monthlyData,
                                    options: {
                                        responsive: true,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    precision: 0
                                                }
                                            }
                                        }
                                    }
                                });

                                // Configuration du graphique par jour de la semaine
                                const weekdayData = {
                                    labels: ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'],
                                    datasets: [{
                                        label: 'Nombre de réservations',
                                        data: @json($weekdayData),
                                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                                        borderColor: 'rgba(54, 162, 235, 1)',
                                        borderWidth: 1
                                    }]
                                };

                                const ctx3 = document.getElementById('weekdayChart').getContext('2d');
                                new Chart(ctx3, {
                                    type: 'bar',
                                    data: weekdayData,
                                    options: {
                                        responsive: true,
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                ticks: {
                                                    precision: 0
                                                }
                                            }
                                        }
                                    }
                                });
                            });
                        </script>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
