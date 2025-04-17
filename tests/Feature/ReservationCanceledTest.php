<?php

namespace Tests\Feature;

use App\Mail\ReservationCanceled;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ReservationCanceledTest extends TestCase
{
    use RefreshDatabase;

    public function test_reservation_canceled_email_has_correct_content()
    {
        // Arrange
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
        ]);

        // Configurer Mail pour intercepter les emails
        Mail::fake();

        // Act
        $mailable = new ReservationCanceled($reservation);

        // Assert
        $mailable->assertSeeInHtml($reservation->id);
        $mailable->assertHasSubject('Confirmation de votre annulation');
        $mailable->assertHasFrom(config('mail.from.address'), config('mail.from.name'));
    }

    public function test_reservation_canceled_email_contains_reservation_data()
    {
        // Arrange
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $mailable = new ReservationCanceled($reservation);

        // Assert
        $this->assertEquals($reservation->id, $mailable->reservation->id);
        $this->assertEquals($reservation->user_id, $mailable->reservation->user_id);
    }

    public function test_reservation_canceled_email_renders_correct_view()
    {
        // Arrange
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act
        $mailable = new ReservationCanceled($reservation);

        // Assert
        $mailable->assertViewIs('emails.reservation-canceled');
    }

    public function test_reservation_canceled_email_is_queued()
    {
        // Arrange
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act & Assert
        Mail::fake();

        Mail::to($user->email)->send(new ReservationCanceled($reservation));

        Mail::assertQueued(ReservationCanceled::class, function ($mail) use ($reservation) {
            return $mail->reservation->id === $reservation->id;
        });
    }

    public function test_reservation_canceled_email_is_sent_to_correct_user()
    {
        // Arrange
        $user = User::factory()->create();
        $reservation = Reservation::factory()->create([
            'user_id' => $user->id,
        ]);

        // Act & Assert
        Mail::fake();

        Mail::to($user->email)->send(new ReservationCanceled($reservation));

        Mail::assertQueued(ReservationCanceled::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
