<?php

namespace App\Jobs;

use App\Models\Accounts\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

class SendInvoiceWhatsappReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 60;

    protected $invoiceId;
    protected $newStage;
    protected $messageBody;

    /**
     * Create a new job instance.
     */
    public function __construct(int $invoiceId, int $newStage, string $messageBody)
    {
        $this->invoiceId = $invoiceId;
        $this->newStage = $newStage;
        $this->messageBody = $messageBody;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $invoice = Invoice::with('buyer.dealer', 'invoicePayment')->find($this->invoiceId);
        if (!$invoice) {
            return;
        }

        $phone = $invoice->buyer->dealer->phone ?? null;
        if (!$phone) {
            Log::warning("Invoice #{$this->invoiceId} has no dealer phone number.");
            return;
        }

        $formattedPhone = $this->formatPhoneNumber($phone);
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (!$sid || !$token || !$from) {
            Log::error("Twilio credentials not configured for job.");
            return;
        }

        try {
            $twilio = app()->bound(Client::class)
                ? app(Client::class)
                : new Client($sid, $token);

            $twilio->messages->create(
                "whatsapp:" . $formattedPhone,
                [
                    'from' => $from,
                    'body' => $this->messageBody,
                ]
            );

            $invoice->update([
                'whatsapp_reminder_stage' => $this->newStage
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to send WhatsApp message to {$formattedPhone} for Invoice #{$invoice->invoice_no}: " . $e->getMessage());
            throw $e; // Throw exception to trigger queue retries
        }
    }

    /**
     * Format phone number to E.164.
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // If it starts with +, return as is
        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        // If it is 10 digits, prepend +91 (India)
        if (strlen($phone) === 10) {
            return '+91' . $phone;
        }

        // If it starts with 91 and has 12 digits, prepend +
        if (strlen($phone) === 12 && str_starts_with($phone, '91')) {
            return '+' . $phone;
        }

        // Otherwise prepend +
        return '+' . $phone;
    }
}
