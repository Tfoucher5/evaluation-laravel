<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Session;
use App\Http\Services\ReservationService;
use App\Http\Services\SalleService;
use Illuminate\View\View;
use App\Models\Reservation;
use App\Models\Salle;
use Carbon\Carbon;
use App\Mail\ReservationCreated;
use App\Mail\ReservationCanceled;
use Illuminate\Support\Facades\Mail;

class ReservationController extends Controller
{
    private const ABILITY = 'reservations';

    private const PATH_VIEWS = 'admin.reservations';

    /**
     * @var ReservationService
     */
    private $service;

    /**
     * @var SalleService
     */
    private $salleService;

    /**
     * Constructor
     *
     * @param  ReservationService  $service
     * @param  SalleService  $salleService
     */
    public function __construct(ReservationService $service, SalleService $salleService)
    {
        $this->middleware('auth');
        $this->service = $service;
        $this->salleService = $salleService;
        Session::put('level_menu_1', 'admin.reservations');
        Session::put('level_menu_2', self::ABILITY);
    }

    /**
     * Index des réservations
     *
     * @return mixed|RedirectResponse|View
     */
    public function index()
    {
        // Différencier les réservations à afficher selon le rôle
        if (auth()->user()->isA('salarie')) {
            // Si c'est un salarié, on ne montre que ses propres réservations
            $reservations = $this->service->getUserUpcomingReservations(auth()->user()->id);
            $canceledReservations = $this->service->getUserCanceledReservations(auth()->user()->id);
        } else {
            // Si c'est un admin, on montre toutes les réservations
            $reservations = $this->service->getAllReservations();
            $canceledReservations = $this->service->getAllCanceledReservations();
        }

        return view(self::PATH_VIEWS . '.index', compact('reservations', 'canceledReservations'));
    }

    /**
     * Affiche le formulaire de création d'une réservation
     *
     * @return View|RedirectResponse
     */
    public function create()
    {

        $salles = $this->salleService->getAllSalles();

        // Vérifier si des salles existent
        if ($salles->isEmpty()) {
            return redirect()->route('reservations.index')->with('message', 'Aucune salle disponible pour réservation.');
        }

        return view(self::PATH_VIEWS . '.add-modify', compact('salles'));



    }

    /**
     * Affiche le formulaire de modification d'une réservation
     * @return \Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit()
    {
        /** @var \App\Models\Reservation $reservation */
        $reservation = Reservation::with('salle')->findOrFail(request()->id);

        // Vérifier si l'utilisateur a le droit de modifier cette réservation
        if (auth()->user()->isA('salarie') && $reservation->user_id !== auth()->user()->id) {
            Session::put('message', 'Vous n\'avez pas le droit de modifier cette réservation.');
            return redirect()->route('reservations.index');
        }

        $salles = $this->salleService->getAllSalles();

        return view(self::PATH_VIEWS . '.add-modify', compact('reservation', 'salles'));
    }

    /**
     * Créer une nouvelle réservation
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'salle_id' => 'required|exists:salles,id',
            'heure_debut' => 'required|date_format:Y-m-d\TH:i',
            'heure_fin' => 'required|date_format:Y-m-d\TH:i|after:heure_debut',
        ]);

        $heureDebut = Carbon::createFromFormat('Y-m-d\TH:i', $request->heure_debut);
        $heureFin = Carbon::createFromFormat('Y-m-d\TH:i', $request->heure_fin);

        // Vérifier la disponibilité de la salle
        if (!$this->service->isSalleAvailable($request->salle_id, $heureDebut, $heureFin)) {
            return back()->withInput()->withErrors(['message' => 'La salle n\'est pas disponible pour cette période.']);
        }

        // Création de la réservation
        $reservation = $this->service->createReservation([
            'salle_id' => $request->salle_id,
            'user_id' => auth()->user()->id,
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
        ]);

        // Message de succès après création
        Session::put('success', 'Réservation créée avec succès.');
        Mail::to(auth()->user()->email)->send(new ReservationCreated($reservation));

        return redirect()->route('reservations.index');
    }

    /**
     * Mise à jour d'une réservation existante
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'salle_id' => 'required|exists:salles,id',
            'heure_debut' => 'required|date_format:Y-m-d\TH:i',
            'heure_fin' => 'required|date_format:Y-m-d\TH:i|after:heure_debut',
        ]);

        $heureDebut = Carbon::createFromFormat('Y-m-d\TH:i', $request->heure_debut);
        $heureFin = Carbon::createFromFormat('Y-m-d\TH:i', $request->heure_fin);

        $reservation = Reservation::findOrFail($id);

        // Vérifier si l'utilisateur a le droit de modifier cette réservation
        if (auth()->user()->isA('salarie') && $reservation->user_id != auth()->user()->id) {
            Session::put('message', 'Vous n\'avez pas le droit de modifier cette réservation.');
            return redirect()->route('reservations.index');
        }

        // Vérifier la disponibilité de la salle (en excluant la réservation actuelle)
        if (!$this->service->isSalleAvailable($request->salle_id, $heureDebut, $heureFin, $id)) {
            return back()->withInput()->withErrors(['message' => 'La salle n\'est pas disponible pour cette période.']);
        }

        // Mise à jour de la réservation
        $this->service->updateReservation($reservation, [
            'salle_id' => $request->salle_id,
            'heure_debut' => $heureDebut,
            'heure_fin' => $heureFin,
        ]);

        // Message de succès après modification
        Session::put('success', 'Réservation modifiée avec succès.');

        return redirect()->route('reservations.index');
    }

    /**
     * Supprime une réservation
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $reservation = Reservation::findOrFail($id);

        // Vérifier si l'utilisateur a le droit de supprimer cette réservation
        if (auth()->user()->isA('salarie') && $reservation->user_id != auth()->user()->id) {
            Session::put('message', 'Vous n\'avez pas le droit de supprimer cette réservation.');
            return redirect()->route('reservations.index');
        }

        $reservation->delete();

        // Message de succès après suppression
        Session::put('success', 'Réservation annulée avec succès.');
        Mail::to($reservation->user->email)->send(new ReservationCanceled($reservation));

        return redirect()->route('reservations.index');
    }
}
