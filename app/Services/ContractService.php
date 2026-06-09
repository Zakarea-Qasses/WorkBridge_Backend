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
    private float $commissionRate = 0.20;

    public function createFromApplication(Application $application): Contract
    {
        $amount = (float) $application->price;
        $split = $this->splitAmount($amount);

        return Contract::firstOrCreate(
            ['application_id' => $application->id],
            [
                'client_id' => $application->project->user_id,
                'freelancer_id' => $application->user_id,
                'user_project_id' => $application->user_project_id,
                'amount' => $amount,
                'commission_amount' => $split['commission'],
                'freelancer_amount' => $split['freelancer'],
                'status' => 'pending',
            ]
        );
    }

    public function createFromServiceRequest(ServiceRequest $serviceRequest): Contract
    {
        $amount = (float) $serviceRequest->service->price;
        $split = $this->splitAmount($amount);

        return Contract::firstOrCreate(
            ['service_request_id' => $serviceRequest->id],
            [
                'client_id' => $serviceRequest->client_id,
                'freelancer_id' => $serviceRequest->service->user_id,
                'amount' => $amount,
                'commission_amount' => $split['commission'],
                'freelancer_amount' => $split['freelancer'],
                'status' => 'pending',
            ]
        );
    }

    public function createFromJobPost(JobPost $jobPost, User $freelancer, float $amount): Contract
    {
        $split = $this->splitAmount($amount);

        return Contract::create([
            'client_id' => $jobPost->company->user_id,
            'freelancer_id' => $freelancer->id,
            'job_post_id' => $jobPost->id,
            'amount' => $amount,
            'commission_amount' => $split['commission'],
            'freelancer_amount' => $split['freelancer'],
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
            $escrowWallet = $this->systemWallet('escrow');

            if ($clientWallet->balance < $contract->amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient wallet balance.',
                ]);
            }

            $this->moveOut(
                $clientWallet,
                $contract->client_id,
                'contract_fund',
                (float) $contract->amount,
                'Contract amount moved to escrow wallet'
            );

            $this->moveIn(
                $escrowWallet,
                $contract->client_id,
                'escrow_receive',
                (float) $contract->amount,
                'Escrow wallet received contract amount'
            );

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
            $escrowWallet = $this->systemWallet('escrow');
            $clientWallet = $this->userWallet($contract->client_id);

            if ($escrowWallet->balance < $contract->amount) {
                throw ValidationException::withMessages([
                    'escrow' => 'Escrow wallet balance is not enough to refund this contract.',
                ]);
            }

            $this->moveOut(
                $escrowWallet,
                $contract->client_id,
                'escrow_refund',
                (float) $contract->amount,
                'Escrow wallet refunded contract amount'
            );

            $this->moveIn(
                $clientWallet,
                $contract->client_id,
                'refund',
                (float) $contract->amount,
                'Contract amount refunded to client'
            );

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

    private function releasePayment(Contract $contract): Contract
    {
        return DB::transaction(function () use ($contract) {
            $escrowWallet = $this->systemWallet('escrow');
            $freelancerWallet = $this->userWallet($contract->freelancer_id);
            $adminWallet = $this->systemWallet('admin');

            if ($escrowWallet->balance < $contract->amount) {
                throw ValidationException::withMessages([
                    'escrow' => 'Escrow wallet balance is not enough to release this contract.',
                ]);
            }

            $this->moveOut(
                $escrowWallet,
                $contract->client_id,
                'escrow_release',
                (float) $contract->amount,
                'Escrow wallet released contract amount'
            );

            $this->moveIn(
                $freelancerWallet,
                $contract->freelancer_id,
                'contract_payment',
                (float) $contract->freelancer_amount,
                'Contract payment released to freelancer'
            );

            $this->moveIn(
                $adminWallet,
                $contract->client_id,
                'platform_commission',
                (float) $contract->commission_amount,
                'Platform commission received by admin wallet'
            );

            $contract->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return $contract->fresh();
        });
    }

    private function userWallet(int $userId): Wallet
    {
        return Wallet::where('user_id', $userId)
            ->where('type', 'user')
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function systemWallet(string $type): Wallet
    {
        Wallet::firstOrCreate(
            ['type' => $type],
            ['balance' => 0, 'is_active' => true]
        );

        return Wallet::where('type', $type)
            ->where('is_active', true)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function moveIn(Wallet $wallet, ?int $userId, string $type, float $amount, string $description): WalletTransaction
    {
        $before = (float) $wallet->balance;
        $after = $before + $amount;

        $wallet->update(['balance' => $after]);

        return $this->transaction($wallet, $userId, $type, 'credit', $amount, $before, $after, $description);
    }

    private function moveOut(Wallet $wallet, ?int $userId, string $type, float $amount, string $description): WalletTransaction
    {
        $before = (float) $wallet->balance;
        $after = $before - $amount;

        $wallet->update(['balance' => $after]);

        return $this->transaction($wallet, $userId, $type, 'debit', $amount, $before, $after, $description);
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

    private function splitAmount(float $amount): array
    {
        $commission = round($amount * $this->commissionRate, 2);

        return [
            'commission' => $commission,
            'freelancer' => round($amount - $commission, 2),
        ];
    }
}
