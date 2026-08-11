<?php

namespace App\Console\Commands;

use App\Models\Beneficiary;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Console\Command;

class NotifyEligibleBeneficiaries extends Command
{
    protected $signature = 'crisimap:notify-eligible-beneficiaries';
    protected $description = 'Notify focal persons when the 90-day assistance cooldown has ended.';

    public function handle(): int
    {
        $beneficiaries = Beneficiary::query()->active()->whereIn('status', ['Approved', 'Released', 'Completed'])->whereDate('date_of_assistance', now()->subDays(90)->toDateString())->get();
        $recipients = User::query()->whereIn('role', ['focal_person', 'admin', 'super_admin'])->get();

        foreach ($beneficiaries as $beneficiary) {
            foreach ($recipients as $recipient) {
                $recipient->notify(new WorkflowNotification('Beneficiary eligible to reapply', "{$beneficiary->name} has completed the 90-day cooldown.", route('beneficiaries.show', $beneficiary)));
            }
        }

        $this->info("Sent cooldown notifications for {$beneficiaries->count()} beneficiary record(s).");
        return self::SUCCESS;
    }
}
