<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\ConfirmEmail;
use Illuminate\Support\Facades\Mail;

class RegisterUserMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $url;
    
    public function __construct($email,$url)
    {
        $this->email=$email;
        $this->url=$url;
    }

    /**
     * handle the message.
     *
     * @return $this
     */
    public function handle()
    {
        // send mail 
        Mail::to($this->email)->send(new ConfirmEmail($this->url,'khawars282@gmail.com'));
    }
}
