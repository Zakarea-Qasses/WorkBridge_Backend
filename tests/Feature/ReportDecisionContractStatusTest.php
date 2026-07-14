<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\Report;
use App\Models\User;
use App\Models\Wallet;
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

    public function test_accepted_funded_dispute_refunds_payer_from_escrow(): void
    {
        [$admin, $client, $freelancer] = $this->users();
        $contract = $this->contract($client, $freelancer);
        $contract->update(['funded_at' => now()]);
        $report = $this->report($contract, $client);

        $clientWallet = Wallet::create([
            'user_id' => $client->id,
            'type' => 'user',
            'balance' => 25,
            'is_active' => true,
        ]);
        $escrowWallet = Wallet::where('type', 'escrow')->firstOrFail();
        $escrowWallet->update(['balance' => 500, 'is_active' => true]);

        Sanctum::actingAs($admin);

        $this->putJson("/api/reports/{$report->id}/decision", [
            'status' => 'accepted',
            'admin_decision' => 'Refund the paying party.',
        ])->assertOk();

        $this->assertSame('refunded', $contract->fresh()->status);
        $this->assertSame('525.00', $clientWallet->fresh()->balance);
        $this->assertSame('0.00', $escrowWallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $escrowWallet->id,
            'type' => 'escrow_refund',
            'direction' => 'debit',
            'amount' => 500,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $clientWallet->id,
            'type' => 'refund',
            'direction' => 'credit',
            'amount' => 500,
        ]);
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
