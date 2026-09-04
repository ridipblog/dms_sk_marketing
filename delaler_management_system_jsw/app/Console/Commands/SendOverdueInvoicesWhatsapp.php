<?php

namespace App\Console\Commands;

use App\Models\Accounts\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Jobs\SendInvoiceWhatsappReminderJob;

class SendOverdueInvoicesWhatsapp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:send-overdue-whatsapp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily WhatsApp reminders for overdue invoices at specific intervals';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting checks for overdue invoices...');

        $today = Carbon::today();
        $dispatchedCount = 0;

        // Process in batches of 200 to protect memory and prevent timeouts
        Invoice::where('invoice_status', 1)
            ->where('due_date', '<', $today)
            ->where(function ($query) {
                $query->whereNull('manual_amount_update')
                      ->orWhere('manual_amount_update', '!=', 1);
            })
            ->whereHas('invoicePayment', function ($query) {
                $query->where('outstanding_amount', '>', 0);
            })
            ->with(['buyer.dealer', 'invoicePayment'])
            ->chunkById(200, function ($overdueInvoices) use ($today, &$dispatchedCount) {
                foreach ($overdueInvoices as $invoice) {
                    $dueDate = Carbon::parse($invoice->due_date);
                    $daysOverdue = (int) abs($today->diffInDays($dueDate));

                    $currentStage = (int)$invoice->whatsapp_reminder_stage;
                    $newStage = null;
                    $messageBody = null;

                    if ($daysOverdue >= 10 && $currentStage < 3) {
                        $newStage = 3;
                        $messageBody = "Dear *" . ($invoice->buyer->dealer->dealer_name ?? 'Valued Customer') . "*, this is a final reminder that invoice *" . $invoice->invoice_no . "* is overdue by " . $daysOverdue . " days. Outstanding amount: INR " . number_format($invoice->invoicePayment->outstanding_amount, 2) . ". Please settle it immediately.";
                    } elseif ($daysOverdue >= 3 && $currentStage < 2) {
                        $newStage = 2;
                        $messageBody = "Dear *" . ($invoice->buyer->dealer->dealer_name ?? 'Valued Customer') . "*, this is a follow-up reminder that invoice *" . $invoice->invoice_no . "* is overdue by " . $daysOverdue . " days. Outstanding amount: INR " . number_format($invoice->invoicePayment->outstanding_amount, 2) . ". Please make the payment.";
                    } elseif ($daysOverdue >= 1 && $currentStage < 1) {
                        $newStage = 1;
                        $messageBody = "Dear *" . ($invoice->buyer->dealer->dealer_name ?? 'Valued Customer') . "*, your invoice *" . $invoice->invoice_no . "* with due date " . $invoice->due_date . " is now overdue. Outstanding amount: INR " . number_format($invoice->invoicePayment->outstanding_amount, 2) . ". Please clear the payment.";
                    }

                    if ($newStage && $messageBody) {
                        $phone = $invoice->buyer->dealer->phone ?? null;
                        if (!$phone) {
                            $this->warn("Invoice #{$invoice->id} has no dealer phone number.");
                            continue;
                        }

                        $this->info("Queueing WhatsApp reminder for Invoice #{$invoice->invoice_no} (Stage {$newStage})");
                        SendInvoiceWhatsappReminderJob::dispatch($invoice->id, $newStage, $messageBody);
                        $dispatchedCount++;
                    }
                }
            });

        $this->info("Completed checks. Dispatched {$dispatchedCount} reminders to the queue.");
        return Command::SUCCESS;
    }
}
