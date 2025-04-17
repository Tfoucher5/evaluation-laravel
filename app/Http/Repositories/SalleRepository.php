<?php

namespace App\Http\Repositories;

use App\Models\Salle;

class SalleRepository
{
    /**
     * @var Salle
     */
    protected $salle;

    /**
     * Constructor
     *
     * @param  Salle  $salle
     */
    public function __construct(Salle $salle)
    {
        $this->salle = $salle;
    }

    /**
     * Get all salles
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Salle>
     */
    public function getAllSalles(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->salle->all();
    }

    /**
     * Save or update a salle
     *
     * @param array<string, mixed> $data
     * @param Salle|null $salle
     * @return Salle
     */
    public function saveOrUpdateSalle(array $data, Salle $salle = null): Salle
    {
        $salle = $salle ?? new Salle();

        $salle->nom = $data['nom'];
        $salle->capacite = $data['capacite'];
        $salle->surface = $data['surface'];
        $salle->save();

        return $salle;
    }
}
