<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Contract;
use App\Models\JobPost;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContractService
{
    private float $commissionRate = 0.10;

    public function createFromApplication(Application $application): Contract
    {
        return Contract::firstOrCreate(
            ['application_id' => $application->id],
            [
                'client_id' => $application->project->user_id,
                'freelancer_id' => $application->user_id,
                'user_project_id' => $application->user_project_id,
                'amount' => $application->price,
                'commission_amount' => round($application->price * $this->commissionRate, 2),
                'freelancer_amount' => round($application->price - ($application->price * $this->commissionRate), 2),
                'status' => 'pending',
            ]
        );
    }

    public function createFromServiceRequest(ServiceRequest $serviceRequest): Contract
    {
        return Contract::firstOrCreate(
            ['service_request_id' => $serviceRequest->id],
            [
                'client_id' => $serviceRequest->client_id,
                'freelancer_id' => $serviceRequest->service->user_id,
                'amount' => $serviceRequest->service->price,
                'commission_amount' => round($serviceRequest->service->price * $this->commissionRate, 2),
                'freelancer_amount' => round($serviceRequest->service->price - ($serviceRequest->service->price * $this->commissionRate), 2),
                'status' => 'pending',
            ]
        );
    }

    public function createFromJobPost(JobPost $jobPost, User $freelancer, float $amount): Contract
    {
        $commission = round($amount * $this->commissionRate, 2);

        return Contract::create([
            'client_id' => $jobPost->company->user_id,
            'freelancer_id' => $freelancer->id,
            'job_post_id' => $jobPost->id,
            'amount' => $amount,
            'commission_amount' => $commission,
            'freelancer_amount' => round($amount - $commission, 2),
            'status' => 'pending',
        ]);
    }

    public function fund(Contract $contract): Contract
    {
        if ($contract->status !== 'pending') {
            throw ValidationException::withMessages([
                'contract' => 'Contract cannot be funded in its current status.',
            ]);
        }

        return DB::transaction(function () use ($contract) {
            $clientWallet = $this->userWallet($contract->client_id);

            if ($clientWallet->balance < $contract->amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient wallet balance.',
                ]);
            }

            $before = $clientWallet->balance;
            $after = $before - $contract->amount;

            $clientWallet->update(['balance' => $after]);

            $this->transaction($clientWallet, $contract->client_id, 'escrow_hold', 'debit', $contract->amount, $before, $after, 'Contract amount reserved');

            $contract->update([
                'status' => 'funded',
                'funded_at' => now(),
            ]);

            return $contract->fresh();
        });
    }

    public function complete(Contract $contract): Contract
    {
        if (! in_array($contract->status, ['funded', 'in_progress'], true)) {
            throw ValidationException::withMessages([
                'contract' => 'Contract cannot be completed in its current status.',
            ]);
        }

        return $this->releasePayment($contract);
    }

    public function releaseFreelancerFromDispute(Contract $contract): Contract
    {
        if ($contract->status !== 'dispute') {
            throw ValidationException::withMessages([
                'contract' => 'Only disputed contracts can be released by admin.',
            ]);
        }

        return $this->releasePayment($contract);
    }

    private function releasePayment(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract) {
            $freelancerWallet = $this->userWallet($contract->freelancer_id);
            $adminWallet = $this->adminWallet();

            $freelancerBefore = $freelancerWallet->balance;
            $freelancerAfter = $freelancerBefore + $contract->freelancer_amount;
            $freelancerWallet->update(['balance' => $freelancerAfter]);

            $this->transaction($freelancerWallet, $contract->freelancer_id, 'contract_payment', 'credit', $contract->freelancer_amount, $freelancerBefore, $freelancerAfter, 'Contract payment released');

            $adminBefore = $adminWallet->balance;
            $adminAfter = $adminBefore + $contract->commission_amount;
            $adminWallet->update(['balance' => $adminAfter]);

            $this->transaction($adminWallet, $contract->client_id, 'commission', 'credit', $contract->commission_amount, $adminBefore, $adminAfter, 'Contract commission');

            $contract->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $contract->fresh();
        });
    }

    public function cancel(Contract $contract): Contract
    {
        if ($contract->status === 'completed') {
            throw ValidationException::withMessages([
                'contract' => 'Completed contract cannot be canceled.',
            ]);
        }

        if ($contract->status === 'pending') {
            $contract->update(['status' => 'canceled']);
            return $contract->fresh();
        }

        return $this->refundClient($contract, 'canceled');
    }

    public function refundClient(Contract $contract, string $status = 'refunded'): Contract
    {
        if (! in_array($contract->status, ['funded', 'in_progress', 'dispute'], true)) {
            throw ValidationException::withMessages([
                'contract' => 'Contract cannot be refunded in its current status.',
            ]);
        }

        return DB::transaction(function () use ($contract, $status) {
            $clientWallet = $this->userWallet($contract->client_id);
            $before = $clientWallet->balance;
            $after = $before + $contract->amount;

            $clientWallet->update(['balance' => $after]);

            $this->transaction($clientWallet, $contract->client_id, 'refund', 'credit', $contract->amount, $before, $after, 'Contract amount refunded');

            $contract->update(['status' => $status]);

            return $contract->fresh();
        });
    }

    public function openDispute(Contract $contract): Contract
    {
        if (! in_array($contract->status, ['funded', 'in_progress'], true)) {
            throw ValidationException::withMessages([
                'contract' => 'Only funded contracts can enter dispute.',
            ]);
        }

        $contract->update(['status' => 'dispute']);

        return $contract->fresh();
    }

    private function userWallet(int $userId): Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('type', 'user')
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function adminWallet(): Wallet
    {
        return Wallet::firstOrCreate(
            ['type' => 'admin'],
            ['balance' => 0, 'is_active' => true]
        );
    }

    private function transaction(Wallet $wallet, ?int $userId, string $type, string $direction, float $amount, float $before, float $after, string $description): WalletTransaction
    {
        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $userId,
            'type' => $type,
            'direction' => $direction,
            'amount' => $amount,
            'balance_before' => $before,
            'balance_after' => $after,
            'status' => 'completed',
            'description' => $description,
        ]);
    }
}
