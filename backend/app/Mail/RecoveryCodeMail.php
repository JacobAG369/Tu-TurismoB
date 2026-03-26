<?php

// el correo que le llega al usuario cuando olvidó su contraseña (nos pasa a todos).

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecoveryCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $code  Código de 6 dígitos generado aleatoriamente
     * @param string $email Correo destinatario (para mostrarlo en la vista)
     */
    public function __construct(
        public readonly string $code,
        public readonly string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu-Turismo — Código de recuperación de contraseña',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recovery_code',
            with: [
                'code'  => $this->code,
                'email' => $this->email,
            ],
        );
    }
}
