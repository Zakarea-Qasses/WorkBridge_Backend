<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportDecisionContractStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepted_dispute_resumes_contract_when_no_pending_disputes_remain(): void
    {
        [$admin, $client, $freelancer] = $this->users();
        $contract = $this->contract($client, $freelancer);
        $report = $this->report($contract, $client);

        Sanctum::actingAs($admin);

        $this->putJson("/api/reports/{$report->id}/decision", [
            'status' => 'accepted',
            'admin_decision' => 'Reviewed and resolved.',
        ])->assertOk();

        $this->assertSame('in_progress', $contract->fresh()->status);
    }

    public function test_contract_stays_in_dispute_while_another_report_is_pending(): void
    {
        [$admin, $client, $freelancer] = $this->users();
        $contract = $this->contract($client, $freelancer);
        $firstReport = $this->report($contract, $client);
        $this->report($contract, $freelancer);

        Sanctum::actingAs($admin);

        $this->putJson("/api/reports/{$firstReport->id}/decision", [
            'status' => 'rejected',
        ])->assertOk();

        $this->assertSame('dispute', $contract->fresh()->status);
    }

    private function users(): array
    {
        $admin = $this->user('Admin', 'decision-admin@example.com', 'admin');
        $client = $this->user('Client', 'decision-client@example.com', 'company');
        $freelancer = $this->user('Freelancer', 'decision-freelancer@example.com', 'personal');

        return [$admin, $client, $freelancer];
    }

    private function user(string $name, string $email, string $role): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    private function contract(User $client, User $freelancer): Contract
    {
        return Contract::create([
            'client_id' => $client->id,
            'freelancer_id' => $freelancer->id,
            'amount' => 500,
            'commission_amount' => 0,
            'freelancer_amount' => 500,
            'status' => 'dispute',
        ]);
    }

    private function report(Contract $contract, User $reporter): Report
    {
        return Report::create([
            'reporter_id' => $reporter->id,
            'target_type' => 'contract',
            'target_id' => $contract->id,
            'contract_id' => $contract->id,
            'title' => 'Contract dispute',
            'category' => 'dispute',
            'priority' => 'high',
            'description' => 'Dispute evidence and details.',
            'status' => 'pending',
        ]);
    }
}
