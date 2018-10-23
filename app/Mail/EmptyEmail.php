<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class EmptyEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $demo;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($demo)
    {
        $this->demo = $demo;
    }

    /**
     * Build the message.
     *
     * @return $this
     * @see https://code.tutsplus.com/tutorials/how-to-send-emails-in-laravel--cms-30046
     */
    public function build()
    {
        //return $this->view('view.name');
        return $this
            ->subject($this->demo->{'subject'})
            ->from('nextbb@yandex.ru')
            ->view('mails.demo')
            ->text('mails.demo_plain')
            ->with(
                [
                    'testVarOne' => '1',
                    'testVarTwo' => '2',
                ]);
    }
}
