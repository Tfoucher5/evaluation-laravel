<x-app-layout>
  <x-slot:title>Liste des Réservations</x-slot:title>
  <div class="container mt-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
          <h1 class="h3">Liste des Réservations</h1>
          <a href="{{ route('reservations.create') }}" class="btn btn-success">Créer une réservation</a>
      </div>

      <!-- Réservations actives -->
      <div class="card mb-4">
          <div class="card-header bg-primary text-white">
              <h2 class="h5 mb-0">Réservations actives</h2>
          </div>
          <div class="card-body">
              @if($reservations->isEmpty())
                  <div class="alert alert-info">
                      Aucune réservation active disponible.
                  </div>
              @else
                  <div class="table-responsive">
                      <table class="table table-striped table-bordered align-middle">
                          <thead class="table-dark">
                              <tr>
                                  <th>Salle</th>
                                  <th>Date de début</th>
                                  <th>Date de fin</th>
                                  @if(!auth()->user()->isA('salarie'))
                                      <th>Réservé par</th>
                                  @endif
                                  <th>Actions</th>
                              </tr>
                          </thead>
                          <tbody>
                              @foreach($reservations as $reservation)
                                  <tr>
                                      <td>{{ $reservation->salle->nom }}</td>
                                      <td>{{ \Carbon\Carbon::parse($reservation->heure_debut)->format('d/m/Y H:i') }}</td>
                                      <td>{{ \Carbon\Carbon::parse($reservation->heure_fin)->format('d/m/Y H:i') }}</td>
                                      @if(!auth()->user()->isA('salarie'))
                                          <td>{{ $reservation->user->identity }}</td>
                                      @endif
                                      <td>
                                          <a href="{{ route('reservations.edit', $reservation->id) }}" class="btn btn-sm btn-primary">Modifier</a>

                                          <form action="{{ route('reservations.destroy', $reservation->id) }}" method="POST" class="d-inline">
                                              @csrf
                                              @method('DELETE')
                                              <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Annuler cette réservation ?')">Annuler</button>
                                          </form>
                                      </td>
                                  </tr>
                              @endforeach
                          </tbody>
                      </table>
                  </div>
              @endif
          </div>
      </div>

      <!-- Réservations annulées -->
      <div class="card">
          <div class="card-header bg-secondary text-white">
              <h2 class="h5 mb-0">Réservations annulées</h2>
          </div>
          <div class="card-body">
              @if($canceledReservations->isEmpty())
                  <div class="alert alert-info">
                      Aucune réservation annulée.
                  </div>
              @else
                  <div class="table-responsive">
                      <table class="table table-striped table-bordered align-middle">
                          <thead class="table-dark">
                              <tr>
                                  <th>Salle</th>
                                  <th>Date de début</th>
                                  <th>Date de fin</th>
                                  @if(!auth()->user()->isA('salarie'))
                                      <th>Réservé par</th>
                                  @endif
                                  <th>Date d'annulation</th>
                              </tr>
                          </thead>
                          <tbody>
                              @foreach($canceledReservations as $reservation)
                                  <tr>
                                      <td>{{ $reservation->salle->nom }}</td>
                                      <td>{{ \Carbon\Carbon::parse($reservation->heure_debut)->format('d/m/Y H:i') }}</td>
                                      <td>{{ \Carbon\Carbon::parse($reservation->heure_fin)->format('d/m/Y H:i') }}</td>
                                      @if(!auth()->user()->isA('salarie'))
                                          <td>{{ $reservation->user->identity }}</td>
                                      @endif
                                      <td>{{ \Carbon\Carbon::parse($reservation->deleted_at)->format('d/m/Y H:i') }}</td>
                                  </tr>
                              @endforeach
                          </tbody>
                      </table>
                  </div>
              @endif
          </div>
      </div>
  </div>
</x-app-layout>
