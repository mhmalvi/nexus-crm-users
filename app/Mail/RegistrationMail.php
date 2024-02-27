<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RegistrationMail extends Mailable
{
    use Queueable, SerializesModels;


    public $email;
    public $name;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($email, $name)
    {
        $this->email = $email;
        $this->name = $name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this
            ->subject("Queleads Registration")->with(['email' => $this->email, 'name' => $this->name])
            ->markdown('registration_mail.registration_mail');
        return $this;
    }
}
