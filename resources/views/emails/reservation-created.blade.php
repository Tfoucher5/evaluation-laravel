<p>Bonjour {{ $reservation->user->name }},</p>
<p>Votre réservation de la salle <strong>{{ $reservation->salle->nom }}</strong> a bien été enregistrée.</p>
<p>
    Du : {{ $reservation->heure_debut->format('d/m/Y H:i') }}<br>
    Au : {{ $reservation->heure_fin->format('d/m/Y H:i') }}
</p>
<p>Merci.</p>
