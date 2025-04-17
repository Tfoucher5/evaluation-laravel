<?php

namespace Tests\Feature;

use App\Mail\ReservationCanceled;
use App\Models\Reservation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationCanceledTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_the_correct_subject_and_view()
    {
        // Create a reservation mock or factory
        $reservation = Reservation::factory()->create();

        // Create the mail instance
        $mail = new ReservationCanceled($reservation);

        // Build the mail
        $builtMail = $mail->build();

        // Assert the subject is correct
        $this->assertEquals('Confirmation de votre annulation', $builtMail->subject);

        // Assert the view is correct
        $this->assertEquals('emails.reservation-canceled', $builtMail->view);
    }

    /** @test */
    public function it_contains_the_reservation_data()
    {
        // Create a reservation mock or factory
        $reservation = Reservation::factory()->create();

        // Create the mail instance
        $mail = new ReservationCanceled($reservation);

        // Assert the reservation data is passed to the view
        $this->assertEquals($reservation, $mail->reservation);
    }

    /** @test */
    public function it_can_render_the_email()
    {
        // Create a reservation mock or factory
        $reservation = Reservation::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            // Add other reservation fields as needed
        ]);

        // Create the mail instance
        $mail = new ReservationCanceled($reservation);

        // This will throw an exception if the view cannot be rendered
        $renderedMail = $mail->render();

        // Assert the rendered email contains expected content
        // Note: This assumes the view contains these elements
        $this->assertStringContainsString($reservation->name, $renderedMail);
        $this->assertStringContainsString($reservation->email, $renderedMail);
    }
}
