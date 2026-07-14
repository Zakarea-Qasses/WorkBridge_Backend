<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Http\Request;
use App\Models\Wallet;
use App\Models\WalletRequest;
use App\Services\WalletService;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'message' => 'تم إيداع المبلغ بنجاح.',
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
            'message' => 'تم سحب المبلغ بنجاح.',
            'transaction' => $transaction,
        ]);
    }

    public function myRequests(Request $request)
    {
        $requests = WalletRequest::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => true,
            'requests' => $requests,
        ]);
    }

    public function requestDeposit(Request $request)
    {
        $request->merge([
            'deposit_proof' => $request->filled('deposit_proof')
                ? trim((string) $request->input('deposit_proof'))
                : null,
        ]);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_note' => ['nullable', 'string', 'max:1000'],
            'deposit_proof' => [
                'nullable',
                'string',
                'max:191',
                Rule::unique('wallet_requests', 'deposit_reference'),
            ],
            'deposit_receipt' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'deposit_proof.unique' => 'رقم الإيداع أو مرجع التحويل مستخدم في طلب شحن سابق. استخدم رقماً مختلفاً لكل عملية.',
        ]);

        if (empty($data['deposit_proof']) && ! $request->hasFile('deposit_receipt')) {
            throw ValidationException::withMessages([
                'deposit_proof' => 'أدخل رقم الإيداع أو ارفع صورة إيصال الإيداع كدليل للتحويل.',
                'deposit_receipt' => 'أدخل رقم الإيداع أو ارفع صورة إيصال الإيداع كدليل للتحويل.',
            ]);
        }

        $receiptPath = $request->hasFile('deposit_receipt')
            ? $request->file('deposit_receipt')->store('wallet-receipts', 'public')
            : null;

        $walletRequest = WalletRequest::create([
            'user_id' => $request->user()->id,
            'type' => 'deposit',
            'amount' => $data['amount'],
            'status' => 'pending',
            'payment_note' => $data['payment_note'] ?? null,
            'deposit_reference' => $data['deposit_proof'] ?? null,
            'deposit_receipt_path' => $receiptPath,
        ]);
        
        $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
        UserNotification::create([
            'user_id' => $admin->id,
            'type' => 'wallet_request',
            'title' => 'طلب شحن جديد',
            'message' => 'تم طلب شحن محفظة'.' '.$request->user()->name,
        ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب شحن الرصيد للأدمن.',
            'request' => $walletRequest,
        ], 201);
    }

    public function requestWithdraw(Request $request)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:50'],
            'withdrawal_details' => ['required', 'string', 'max:2000'],
        ]);

        $wallet = Wallet::where('user_id', $request->user()->id)
            ->where('type', 'user')
            ->where('is_active', true)
            ->firstOrFail();

        if ((float) $wallet->balance < (float) $data['amount']) {
            throw ValidationException::withMessages([
                'amount' => 'رصيد المحفظة غير كاف لإرسال طلب السحب.',
            ]);
        }

        $walletRequest = WalletRequest::create([
            'user_id' => $request->user()->id,
            'type' => 'withdraw',
            'amount' => $data['amount'],
            'status' => 'pending',
            'withdrawal_details' => $data['withdrawal_details'],
        ]);
        
        $admins = User::where('role', 'admin')->get();
            foreach ($admins as $admin) {
        UserNotification::create([
            'user_id' => $admin->id,
            'type' => 'wallet_request',
            'title' => 'طلب سحب جديد',
            'message' => 'تم طلب سحب محفظة'.' '.$request->user()->name,
        ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'تم إرسال طلب السحب للأدمن.',
            'request' => $walletRequest,
        ], 201);
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
            'message' => 'تم تحويل المبلغ إلى محفظة الأدمن بنجاح.',
            'transaction' => $transaction,
        ]);
    }

    public function adminTransactions()
    {
        $wallet = \App\Models\Wallet::where('type', 'admin')
            ->with('transactions')
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'wallet' => $wallet,
        ]);
    }

    public function adminEarnings()
    {
        $wallet = \App\Models\Wallet::where('type', 'admin')->firstOrFail();

        return response()->json([
            'status' => true,
            'balance' => $wallet->balance,
            'earnings' => $wallet->transactions()
                ->whereIn('type', ['admin_receive', 'commission', 'platform_commission'])
                ->where('direction', 'credit')
                ->sum('amount'),
        ]);
    }

    public function escrowTransactions()
    {
        $wallet = \App\Models\Wallet::where('type', 'escrow')
            ->with('transactions')
            ->firstOrFail();

        return response()->json([
            'status' => true,
            'wallet' => $wallet,
        ]);
    }

    public function allWallets()
    {
        $wallets = Wallet::with(['user:id,name,email', 'transactions'])
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'wallets' => $wallets,
        ]);
    }

    public function adminWalletRequests(Request $request)
    {
        $data = $request->validate([
            'type' => ['nullable', Rule::in(['deposit', 'withdraw'])],
            'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'search' => ['nullable', 'string', 'max:255'],
        ]);

        $query = WalletRequest::with([
            'user:id,name,email,role,status',
            'reviewer:id,name,email',
        ])->latest();

        if (!empty($data['type'])) {
            $query->where('type', $data['type']);
        }

        if (!empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (!empty($data['search'])) {
            $search = $data['search'];
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json([
            'status' => true,
            'requests' => $query->paginate(20),
        ]);
    }

    public function approveWalletRequest(Request $request, WalletRequest $walletRequest)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($walletRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'تمت معالجة هذا الطلب مسبقا.',
            ]);
        }

        $transaction = $walletRequest->type === 'deposit'
            ? $this->walletService->deposit($walletRequest->user, (float) $walletRequest->amount)
            : $this->walletService->withdraw($walletRequest->user, (float) $walletRequest->amount);

        $walletRequest->update([
            'status' => 'approved',
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        UserNotification::create([
            'user_id' => $walletRequest->user_id,
            'type' => 'job_paused',
            'title' => 'قبول طلب المحفظة',
            'message' => 'تم قبول طلب المحفظة بنجاح.',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم قبول طلب المحفظة بنجاح.',
            'request' => $walletRequest->fresh(['user:id,name,email,role,status', 'reviewer:id,name,email']),
            'transaction' => $transaction,
        ]);
    }

    public function rejectWalletRequest(Request $request, WalletRequest $walletRequest)
    {
        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($walletRequest->status !== 'pending') {
            throw ValidationException::withMessages([
                'request' => 'تمت معالجة هذا الطلب مسبقا.',
            ]);
        }

        $walletRequest->update([
            'status' => 'rejected',
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);
        
        UserNotification::create([
            'user_id' => $walletRequest->user_id,
            'type' => 'job_paused',
            'title' => 'رفض طلب المحفظة',
            'message' => 'تم رفض طلب المحفظة.',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'تم رفض طلب المحفظة.',
            'request' => $walletRequest->fresh(['user:id,name,email,role,status', 'reviewer:id,name,email']),
        ]);
    }
}
