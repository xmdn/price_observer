<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Advert;

class PriceAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $advert;
    public $newPrice;

    /**
     * Create a new message instance.
     */
    public function __construct(Advert $advert, $newPrice)
    {
        $this->advert = $advert;
        $this->newPrice = $newPrice;
    }

    /**
     * Get the builded alert of price changes.
     */
    public function build()
    {
        return $this->subject("OLX Advert Price Changed!")
                    ->view('emails.price_alert')
                    ->with([
                        'title' => $this->advert->title,
                        'oldPrice' => $this->advert->price,
                        'newPrice' => $this->newPrice,
                        'link' => $this->advert->link
                    ]);
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Price Alert',
        );
    }

    /**
     * Get the message content definition.
     */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
