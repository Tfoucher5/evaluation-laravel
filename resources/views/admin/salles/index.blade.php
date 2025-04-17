<x-app-layout>
    <x-slot:title>Liste des Salles</x-slot:title>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h3">Liste des Salles</h1>
            @if (auth()->user()->isA('admin'))
                <a href="{{ route('salles.create') }}" class="btn btn-success">Créer une salle</a>
            @endif
        </div>

        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Nom</th>
                    <th>Capacité</th>
                    <th>Surface (m²)</th>
                    @if (auth()->user()->isA('admin'))
                        <th>Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($salles as $salle)
                    <tr>
                        <td>{{ $salle->nom }}</td>
                        <td>{{ $salle->capacite }}</td>
                        <td>{{ $salle->surface }}</td>
                        @if (auth()->user()->isA('admin'))
                            <td>
                                <a href="{{ route('salles.edit', $salle->id) }}"
                                    class="btn btn-sm btn-primary">Modifier</a>
                                <form action="{{ route('salles.destroy', $salle->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                        onclick="return confirm('Supprimer cette salle ?')">Supprimer</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-app-layout>
