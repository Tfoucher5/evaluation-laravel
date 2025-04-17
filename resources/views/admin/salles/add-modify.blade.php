<x-app-layout>
    <x-slot:title>Liste des Salles</x-slot:title>
    <form action="{{ isset($salle) ? route('salles.update', $salle->id) : route('salles.store') }}" method="POST">
        @csrf
        @if (isset($salle))
            @method('PUT')
        @endif

        <div class="mb-3">
            <label for="nom" class="form-label">Nom</label>
            <input type="text" class="form-control" id="nom" name="nom"
                value="{{ old('nom', $salle->nom ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label for="capacite" class="form-label">Capacité (nombre de places)</label>
            <input type="number" class="form-control" id="capacite" name="capacite"
                value="{{ old('capacite', $salle->capacite ?? '') }}" min="1" required>
        </div>

        <div class="mb-3">
            <label for="surface" class="form-label">Surface (en m²)</label>
            <input type="number" step="0.1" class="form-control" id="surface" name="surface"
                value="{{ old('surface', $salle->surface ?? '') }}" min="0" required>
        </div>

        <button type="submit" class="btn btn-primary">
            {{ isset($salle) ? 'Modifier' : 'Créer' }} la salle
        </button>
    </form>

    <a href="{{ route('salles.index') }}" class="btn btn-secondary">Retour à la liste des salles</a>
</x-app-layout>
