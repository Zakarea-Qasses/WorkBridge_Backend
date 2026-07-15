<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ServiceRequestAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_one_request_rejects_other_pending_requests_for_same_service(): void
    {
        $provider = $this->user('Provider', 'provider@example.com', 'personal');
        $firstClient = $this->user('First client', 'first-client@example.com', 'personal');
        $secondClient = $this->user('Second client', 'second-client@example.com', 'company');
        $thirdClient = $this->user('Third client', 'third-client@example.com', 'personal');

        $service = Service::create([
            'user_id' => $provider->id,
            'category_id' => Category::create(['name' => 'Development'])->id,
            'title' => 'Build a website',
            'description' => 'Website development service',
            'price' => 100,
            'delivery_days' => 5,
            'status' => 'active',
        ]);

        $acceptedRequest = $this->serviceRequest($service, $firstClient, 'First request');
        $rejectedRequest = $this->serviceRequest($service, $secondClient, 'Second request');
        $otherRejectedRequest = $this->serviceRequest($service, $thirdClient, 'Third request');

        Sanctum::actingAs($provider);

        $this->postJson("/api/service-requests/{$acceptedRequest->id}/accept")
            ->assertOk()
            ->assertJsonPath('service_request.status', 'accepted')
            ->assertJsonPath('rejected_request_ids', [$rejectedRequest->id, $otherRejectedRequest->id]);

        $this->assertSame('accepted', $acceptedRequest->fresh()->status);
        $this->assertSame('rejected', $rejectedRequest->fresh()->status);
        $this->assertSame('rejected', $otherRejectedRequest->fresh()->status);
        $this->assertDatabaseHas('contracts', [
            'service_request_id' => $acceptedRequest->id,
            'client_id' => $firstClient->id,
            'freelancer_id' => $provider->id,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $secondClient->id,
            'type' => 'service_request_rejected',
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $thirdClient->id,
            'type' => 'service_request_rejected',
        ]);
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

    private function serviceRequest(Service $service, User $client, string $title): ServiceRequest
    {
        return ServiceRequest::create([
            'service_id' => $service->id,
            'client_id' => $client->id,
            'title' => $title,
            'description' => 'Request details',
            'delivery_days' => 5,
            'status' => 'pending',
        ]);
    }
}
