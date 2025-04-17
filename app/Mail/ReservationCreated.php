<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationCreated extends Mailable
{

    use Queueable, SerializesModels;

    /**
     * L'instance de réservation annulée.
     *
     * @var Reservation
     */
    public $reservation;

    /**
     * Crée une nouvelle instance du message.
     *
     * @param  Reservation  $reservation
     * @return void
     */
    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    /**
     * Construit le message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Confirmation de votre réservation')
                    ->view('emails.reservation-created');
    }
}
