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

    public function test_provider_can_accept_multiple_requests_for_same_service(): void
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

        $firstRequest = $this->serviceRequest($service, $firstClient, 'First request');
        $secondRequest = $this->serviceRequest($service, $secondClient, 'Second request');
        $pendingRequest = $this->serviceRequest($service, $thirdClient, 'Third request');

        Sanctum::actingAs($provider);

        $this->postJson("/api/service-requests/{$firstRequest->id}/accept")
            ->assertOk()
            ->assertJsonPath('service_request.status', 'accepted');
        $this->postJson("/api/service-requests/{$secondRequest->id}/accept")
            ->assertOk()
            ->assertJsonPath('service_request.status', 'accepted');

        $this->assertSame('accepted', $firstRequest->fresh()->status);
        $this->assertSame('accepted', $secondRequest->fresh()->status);
        $this->assertSame('pending', $pendingRequest->fresh()->status);
        $this->assertDatabaseHas('contracts', [
            'service_request_id' => $firstRequest->id,
            'client_id' => $firstClient->id,
            'freelancer_id' => $provider->id,
        ]);
        $this->assertDatabaseHas('contracts', [
            'service_request_id' => $secondRequest->id,
            'client_id' => $secondClient->id,
            'freelancer_id' => $provider->id,
        ]);
        $this->assertDatabaseCount('contracts', 2);
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
