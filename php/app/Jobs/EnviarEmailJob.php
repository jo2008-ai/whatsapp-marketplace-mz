<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $to,
        public Mailable $mailable,
    ) {
    }

    public function handle(): void
    {
        try {
            Mail::to($this->to)->send($this->mailable);
        } catch (\Exception $e) {
            Log::error('Falha ao enviar email', [
                'to' => $this->to,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
