<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminEarningsWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_withdraw_earnings_from_admin_wallet(): void
    {
        $admin = User::create([
            'name' => 'Admin One',
            'email' => 'admin-one@example.com',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $wallet = Wallet::where('type', 'admin')->firstOrFail();
        $wallet->update(['balance' => 250, 'is_active' => true]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/earnings/withdraw', [
            'amount' => 75,
            'payment_method' => 'sham_cash',
            'recipient_account' => '0999999999',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('transaction.type', 'admin_withdrawal')
            ->assertJsonPath('transaction.direction', 'debit')
            ->assertJsonPath('transaction.balance_after', '175.00');

        $this->assertSame('175.00', $wallet->fresh()->balance);
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'user_id' => $admin->id,
            'type' => 'admin_withdrawal',
            'amount' => 75,
        ]);
    }

    public function test_admin_cannot_withdraw_more_than_admin_wallet_balance(): void
    {
        $admin = User::create([
            'name' => 'Admin Two',
            'email' => 'admin-two@example.com',
            'password' => 'password',
            'role' => 'admin',
            'status' => 'active',
        ]);
        $wallet = Wallet::where('type', 'admin')->firstOrFail();
        $wallet->update(['balance' => 40, 'is_active' => true]);

        Sanctum::actingAs($admin);

        $this->postJson('/api/admin/earnings/withdraw', [
            'amount' => 50,
            'payment_method' => 'syriatel_cash',
            'recipient_account' => '0988888888',
        ])->assertUnprocessable()->assertJsonValidationErrors('amount');

        $this->assertSame('40.00', $wallet->fresh()->balance);
        $this->assertDatabaseCount('wallet_transactions', 0);
    }
}
