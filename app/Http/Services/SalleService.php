<?php

namespace App\Http\Services;

use App\Http\Repositories\SalleRepository;
use App\Models\Salle;
use Illuminate\Database\Eloquent\Collection;

class SalleService
{
    /**
     * @var SalleRepository
     */
    protected SalleRepository $repository;

    /**
     * Constructor
     *
     * @param  SalleRepository  $repository
     */
    public function __construct(SalleRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * Get all salles
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Salle>
     */
    public function getAllSalles(): Collection
    {
        return $this->repository->getAllSalles();
    }

    /**
     * Create a new salle
     *
     * @param array<string, mixed> $data
     * @return Salle
     */
    public function createSalle(array $data): Salle
    {
        return $this->repository->saveOrUpdateSalle($data);
    }

    /**
     * Update an existing salle
     *
     * @param Salle $salle
     * @param array<string, mixed> $data
     * @return Salle
     */
    public function updateSalle(Salle $salle, array $data): Salle
    {
        return $this->repository->saveOrUpdateSalle($data, $salle);
    }
}
