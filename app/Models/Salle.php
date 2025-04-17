<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Reservation;

class Salle extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom',
        'capacite',
        'surface'
    ];

    /**
     * Summary of taches
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<Reservation, $this>
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }
}
