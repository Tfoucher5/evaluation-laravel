<x-app-layout>
  <x-slot:title>{{ isset($reservation) ? 'Modifier une Réservation' : 'Créer une Réservation' }}</x-slot:title>

  <div class="container py-5">
      <div class="row justify-content-center">
          <div class="col-md-8">

              <div class="card shadow">
                  <div class="card-header bg-primary text-white">
                      {{ isset($reservation) ? 'Modifier une Réservation' : 'Créer une Réservation' }}
                  </div>

                  <div class="card-body">
                      @if($errors->any())
                          <div class="alert alert-danger">
                              <ul class="mb-0">
                                  @foreach($errors->all() as $error)
                                      <li>{{ $error }}</li>
                                  @endforeach
                              </ul>
                          </div>
                      @endif

                      <form action="{{ isset($reservation) ? route('reservations.update', $reservation->id) : route('reservations.store') }}" method="POST">
                          @csrf
                          @if (isset($reservation))
                              @method('PUT')
                          @endif

                          <div class="mb-3">
                              <label for="salle_id" class="form-label">Salle</label>
                              <select class="form-select" id="salle_id" name="salle_id" required>
                                  <option value="">Sélectionner une salle</option>
                                  @foreach($salles as $salle)
                                      <option value="{{ $salle->id }}"
                                          {{ old('salle_id', isset($reservation) ? $reservation->salle_id : '') == $salle->id ? 'selected' : '' }}>
                                          {{ $salle->nom }} ({{ $salle->capacite }} pers., {{ $salle->surface }} m²)
                                      </option>
                                  @endforeach
                              </select>
                          </div>

                          <div class="mb-3">
                              <label for="heure_debut" class="form-label">Date et heure de début</label>
                              <input type="datetime-local" class="form-control" id="heure_debut" name="heure_debut"
                                  value="{{ old('heure_debut', isset($reservation) ? date('Y-m-d\TH:i', strtotime($reservation->heure_debut)) : '') }}" required>
                          </div>

                          <div class="mb-3">
                              <label for="heure_fin" class="form-label">Date et heure de fin</label>
                              <input type="datetime-local" class="form-control" id="heure_fin" name="heure_fin"
                                  value="{{ old('heure_fin', isset($reservation) ? date('Y-m-d\TH:i', strtotime($reservation->heure_fin)) : '') }}" required>
                          </div>

                          <div class="d-flex justify-content-between">
                              <a href="{{ route('reservations.index') }}" class="btn btn-secondary">Retour</a>
                              <button type="submit" class="btn btn-primary">
                                  {{ isset($reservation) ? 'Modifier' : 'Créer' }} la réservation
                              </button>
                          </div>
                      </form>
                  </div>
              </div>

          </div>
      </div>
  </div>

  <script>
      function formatLocalDatetime(date) {
          const year = date.getFullYear();
          const month = String(date.getMonth() + 1).padStart(2, '0');
          const day = String(date.getDate()).padStart(2, '0');
          const hours = String(date.getHours()).padStart(2, '0');
          const minutes = String(date.getMinutes()).padStart(2, '0');
          return `${year}-${month}-${day}T${hours}:${minutes}`;
      }

      document.addEventListener('DOMContentLoaded', function () {
          const debutInput = document.getElementById('heure_debut');
          const finInput = document.getElementById('heure_fin');

          if (!debutInput.value) {
              const now = new Date();
              const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000);

              const localNow = formatLocalDatetime(now);
              const localOneHourLater = formatLocalDatetime(oneHourLater);

              debutInput.min = localNow;
              debutInput.value = localNow;

              finInput.min = localNow;
              finInput.value = localOneHourLater;
          }

          debutInput.addEventListener('change', function () {
              const startTime = new Date(this.value);
              const minEndTime = new Date(startTime.getTime() + 60 * 60 * 1000);

              finInput.min = formatLocalDatetime(startTime);

              const currentEndTime = new Date(finInput.value);
              if (currentEndTime <= startTime) {
                  finInput.value = formatLocalDatetime(minEndTime);
              }
          });
      });
  </script>
</x-app-layout>
