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
        $this->assertPositiveAmount($amount);
        $this->assertDifferentParties($application->project->user_id, $application->user_id);

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
        $this->assertPositiveAmount($amount);
        $this->assertDifferentParties($serviceRequest->client_id, $serviceRequest->service->user_id);

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
        $this->assertPositiveAmount($amount);
        $this->assertDifferentParties($jobPost->company->user_id, $freelancer->id);

        $split = $this->splitAmount($amount);

        return Contract::firstOrCreate(
            [
                'job_post_id' => $jobPost->id,
                'freelancer_id' => $freelancer->id,
            ],
            [
                'client_id' => $jobPost->company->user_id,
                'amount' => $amount,
                'commission_amount' => $split['commission'],
                'freelancer_amount' => $split['freelancer'],
                'status' => 'pending',
            ]
        );
    }

    public function fund(Contract $contract): Contract
    {
        if ($contract->status !== 'pending') {
            throw ValidationException::withMessages([
                'contract' => 'لا يمكن تمويل العقد في حالته الحالية.',
            ]);
        }

        return DB::transaction(function () use ($contract) {
            $clientWallet = $this->userWallet($contract->client_id);
            $escrowWallet = $this->systemWallet('escrow');

            if ($clientWallet->balance < $contract->amount) {
                throw ValidationException::withMessages([
                    'amount' => 'رصيد المحفظة غير كاف.',
                ]);
            }

            $this->moveOut(
                $clientWallet,
                $contract->client_id,
                'contract_fund',
                (float) $contract->amount,
                'تم تحويل مبلغ العقد إلى محفظة الوسيط'
            );

            $this->moveIn(
                $escrowWallet,
                $contract->client_id,
                'escrow_receive',
                (float) $contract->amount,
                'استلمت محفظة الوسيط مبلغ العقد'
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
                'contract' => 'لا يمكن إكمال العقد في حالته الحالية.',
            ]);
        }

        return $this->releasePayment($contract);
    }

    public function releaseFreelancerFromDispute(Contract $contract): Contract
    {
        if ($contract->status !== 'dispute') {
            throw ValidationException::withMessages([
                'contract' => 'يمكن للأدمن تحرير المبلغ فقط للعقود المتنازع عليها.',
            ]);
        }

        return $this->releasePayment($contract);
    }

    public function cancel(Contract $contract): Contract
    {
        if ($contract->status === 'completed') {
            throw ValidationException::withMessages([
                'contract' => 'لا يمكن إلغاء عقد مكتمل.',
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
                'contract' => 'لا يمكن رد مبلغ العقد في حالته الحالية.',
            ]);
        }

        return DB::transaction(function () use ($contract, $status) {
            $escrowWallet = $this->systemWallet('escrow');
            $clientWallet = $this->userWallet($contract->client_id);

            if ($escrowWallet->balance < $contract->amount) {
                throw ValidationException::withMessages([
                    'escrow' => 'رصيد محفظة الوسيط غير كاف لرد مبلغ هذا العقد.',
                ]);
            }

            $this->moveOut(
                $escrowWallet,
                $contract->client_id,
                'escrow_refund',
                (float) $contract->amount,
                'ردت محفظة الوسيط مبلغ العقد'
            );

            $this->moveIn(
                $clientWallet,
                $contract->client_id,
                'refund',
                (float) $contract->amount,
                'تم رد مبلغ العقد إلى العميل'
            );

            $contract->update(['status' => $status]);

            return $contract->fresh();
        });
    }

    public function openDispute(Contract $contract): Contract
    {
        if (! in_array($contract->status, ['funded', 'in_progress'], true)) {
            throw ValidationException::withMessages([
                'contract' => 'يمكن فتح نزاع فقط على العقود الممولة.',
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
                    'escrow' => 'رصيد محفظة الوسيط غير كاف لتحرير مبلغ هذا العقد.',
                ]);
            }

            $this->moveOut(
                $escrowWallet,
                $contract->client_id,
                'escrow_release',
                (float) $contract->amount,
                'حررت محفظة الوسيط مبلغ العقد'
            );

            $this->moveIn(
                $freelancerWallet,
                $contract->freelancer_id,
                'contract_payment',
                (float) $contract->freelancer_amount,
                'تم تحرير دفعة العقد للمستفيد'
            );

            $this->moveIn(
                $adminWallet,
                $contract->client_id,
                'platform_commission',
                (float) $contract->commission_amount,
                'استلمت محفظة الأدمن عمولة المنصة'
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
        $this->assertPositiveAmount($amount);

        $before = (float) $wallet->balance;
        $after = $before + $amount;

        $wallet->update(['balance' => $after]);

        return $this->transaction($wallet, $userId, $type, 'credit', $amount, $before, $after, $description);
    }

    private function moveOut(Wallet $wallet, ?int $userId, string $type, float $amount, string $description): WalletTransaction
    {
        $this->assertPositiveAmount($amount);

        $before = (float) $wallet->balance;
        $after = $before - $amount;

        if ($after < 0) {
            throw ValidationException::withMessages([
                'amount' => 'Wallet balance cannot be negative.',
            ]);
        }

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
        $this->assertPositiveAmount($amount);

        $commission = round($amount * $this->commissionRate, 2);

        return [
            'commission' => $commission,
            'freelancer' => round($amount - $commission, 2),
        ];
    }

    private function assertPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Amount must be greater than zero.',
            ]);
        }
    }

    private function assertDifferentParties(int $clientId, int $freelancerId): void
    {
        if ($clientId === $freelancerId) {
            throw ValidationException::withMessages([
                'contract' => 'Contract parties must be different users.',
            ]);
        }
    }
}
