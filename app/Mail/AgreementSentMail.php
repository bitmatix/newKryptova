<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class AgreementSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->subject('Kryptova Agreement')
            ->markdown('emails.agreementSentMail')
            ->with(
                [
                    'url' => $this->details['url'],
                    'name' => $this->details['name']
                ]
            );

            if(!empty($this->details['file'])){
                $data = $data->attach(asset($this->details['file']),['as'=>'Service_Level_Agreement_Kryptova.pdf']);
            };

        return $data;
    }
}
