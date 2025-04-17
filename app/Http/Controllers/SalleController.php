<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Session;
use App\Http\Services\SalleService;
use Illuminate\View\View;
use App\Models\Salle;

class SalleController extends Controller
{
    private const ABILITY = 'salles';

    private const PATH_VIEWS = 'admin.salles';

    /**
     * @var SalleService
     */
    private $service;

    /**
     * Constructor
     *
     * @param  SalleService  $service
     */
    public function __construct(SalleService $service)
    {
        $this->middleware('auth');
        $this->service = $service;
        Session::put('level_menu_1', 'salles');
        Session::put('level_menu_2', self::ABILITY);
    }

    /**
     * Index des salles
     *
     * @return mixed|RedirectResponse|View
     */
    public function index()
    {
        $salles = $this->service->getAllSalles();

        // Si aucune salle n'est trouvée
        if ($salles->isEmpty() && auth()->user()->isA('admin')) {
            Session::put('message', 'Aucune salle trouvée. Veuillez en créer une.');
            return view(self::PATH_VIEWS . '.index', compact('salles'));
        }

        return view(self::PATH_VIEWS . '.index', compact('salles'));
    }

    /**
     * Affiche le formulaire de création d'une salle
     *
     * @return RedirectResponse
     */
    public function create()
    {
        if (auth()->user()->isA('salarie')) {
            Session::put('message', 'Vous n\'avez pas accès à cette page.');
            return redirect()->route('dashboard');
        }
        if (auth()->user()->isA('admin')) {

            return redirect()->route('salles.create');
        } else {
            return redirect()->route('salles.index')->with('error', 'Vous n\avez pas l\'autorisation d\'accéder à cette page.');
        }
    }

    /**
     * Affiche le formulaire de création d'une salle
     *
     * @return RedirectResponse
     */
    public function edit()
    {
        if (auth()->user()->isA('salarie')) {
            Session::put('message', 'Vous n\'avez pas accès à cette page.');
            return redirect()->route('dashboard');
        }

        $salle = Salle::findOrFail(request()->id);

        return redirect()->route('salles.edit', compact('salle'));
    }

    /**
     * Créer une nouvelle salle
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'capacite' => 'required|integer|min:1',
            'surface' => 'required|numeric|min:0',
        ]);

        // Création de la salle
        $this->service->createSalle([
            'nom' => $request->nom,
            'capacite' => $request->capacite,
            'surface' => $request->surface,
        ]);

        // Message de succès après création
        Session::put('success', 'Salle créée avec succès.');

        return redirect()->route('salles.index');
    }

    /**
     * Mise à jour d'une salle existante
     *
     * @param Request $request
     * @param int $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'capacite' => 'required|integer|min:1',
            'surface' => 'required|numeric|min:0',
        ]);

        $existingSalle = Salle::where('id', $id)->first();

        // Mise à jour de la salle
        $this->service->updateSalle($existingSalle, [
            'nom' => $request->nom,
            'capacite' => $request->capacite,
            'surface' => $request->surface,
        ]);

        // Message de succès après modification
        Session::put('success', 'Salle modifiée avec succès.');

        return redirect()->route('salles.index');
    }

    /**
     * Supprime une salle
     *
     * @param int $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $salle = Salle::findOrFail($id);
        $salle->delete();

        // Message de succès après suppression
        Session::put('success', 'Salle supprimée avec succès.');

        return redirect()->route('salles.index');
    }
}
