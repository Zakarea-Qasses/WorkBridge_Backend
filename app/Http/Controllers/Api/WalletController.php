<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WalletService;

class WalletController extends Controller
{
   public function __construct(
        protected WalletService $walletService
    ) {}

    public function myWallet(Request $request)
    {
        $wallet = $request->user()
            ->wallet()
            ->with('transactions')
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'wallet' => $wallet,
        ]);
    }

    public function deposit(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $transaction = $this->walletService->deposit(
            $request->user(),
            $data['amount']
        );

        return response()->json([
            'status' => true,
            'message' => 'Deposit completed successfully.',
            'transaction' => $transaction,
        ]);
    }

    public function withdraw(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $transaction = $this->walletService->withdraw(
            $request->user(),
            $data['amount']
        );

        return response()->json([
            'status' => true,
            'message' => 'Withdraw completed successfully.',
            'transaction' => $transaction,
        ]);
    }

    public function transferToAdmin(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $transaction = $this->walletService->transferToAdminWallet(
            $request->user(),
            $data['amount']
        );

        return response()->json([
            'status' => true,
            'message' => 'Amount transferred to admin wallet successfully.',
            'transaction' => $transaction,
        ]);
    }
}