<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\BookingOtp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingCancellationOtp extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public BookingOtp $otp
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Ma xac nhan huy booking {$this->booking->booking_code}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-cancellation-otp',
        );
    }
}
