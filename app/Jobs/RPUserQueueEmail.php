<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\SendUserMultiMail;
use App\Agent;
use Mail;

class RPUserQueueEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $details;
    public $timeout = 7200; // 2 hours

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $ids = $this->details['ids'];
        $input = $this->details['input'];

        foreach ($ids as $key => $value) {
            if($value == 'on') {
                continue;
            }
            $agent = Agent::where('id', $value)->first();
            try {
                // \Log::info('jobOk');
                \Mail::to($agent->email)
                    ->send(new SendUserMultiMail($input));
            } catch (Exception $e) {
            }
        }
    }
}
