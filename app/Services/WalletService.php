<?php
namespace App\Services;

use App\Models\Wallet;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    public function deposit( User $user, float $amount)
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'يجب أن يكون المبلغ أكبر من صفر.',
            ]);
        }

        return DB::transaction(function () use ($user, $amount) {
            $wallet = Wallet::where('user_id', $user->id)
                ->where('type', 'user')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->update([
                'balance' => $balanceAfter,
            ]);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'deposit',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'description' => 'إيداع مبلغ في المحفظة',
            ]);
        });
    }

    public function withdraw( User $user, float $amount)
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'يجب أن يكون المبلغ أكبر من صفر.',
            ]);
        }

        return DB::transaction(function () use ( $user, $amount) {
            $wallet = Wallet::where('user_id', $user->id)
                ->where('type', 'user')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'رصيد المحفظة غير كاف.',
                ]);
            }

            $balanceBefore = $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            $wallet->update([
                'balance' => $balanceAfter,
            ]);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'withdraw',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'status' => 'completed',
                'description' => 'سحب مبلغ من المحفظة',
            ]);
        });
    }

    public function transferToAdminWallet( User $user, float $amount)
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'يجب أن يكون المبلغ أكبر من صفر.',
            ]);
        }

        return DB::transaction(function () use ($user, $amount) {
            $userWallet = Wallet::where('user_id', $user->id)
                ->where('type', 'user')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $adminWallet = Wallet::where('type', 'admin')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            if ($userWallet->balance < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'رصيد المحفظة غير كاف.',
                ]);
            }

            $userBalanceBefore = $userWallet->balance;
            $userBalanceAfter = $userBalanceBefore - $amount;

            $adminBalanceBefore = $adminWallet->balance;
            $adminBalanceAfter = $adminBalanceBefore + $amount;

            $userWallet->update([
                'balance' => $userBalanceAfter,
            ]);

            $adminWallet->update([
                'balance' => $adminBalanceAfter,
            ]);

            $userTransaction = WalletTransaction::create([
                'wallet_id' => $userWallet->id,
                'user_id' => $user->id,
                'type' => 'transfer_to_admin',
                'direction' => 'debit',
                'amount' => $amount,
                'balance_before' => $userBalanceBefore,
                'balance_after' => $userBalanceAfter,
                'status' => 'completed',
                'description' => 'تحويل مبلغ إلى محفظة الأدمن',
            ]);

            WalletTransaction::create([
                'wallet_id' => $adminWallet->id,
                'user_id' => $user->id,
                'type' => 'admin_receive',
                'direction' => 'credit',
                'amount' => $amount,
                'balance_before' => $adminBalanceBefore,
                'balance_after' => $adminBalanceAfter,
                'status' => 'completed',
                'description' => 'استلام مبلغ في محفظة الأدمن',
            ]);

            return $userTransaction;
        });
    }
}
