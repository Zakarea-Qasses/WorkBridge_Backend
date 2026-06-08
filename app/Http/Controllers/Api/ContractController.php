<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\JobPost;
use App\Models\User;
use App\Services\ContractService;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(
        protected ContractService $contractService
    ) {}

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $contracts = Contract::with(['client:id,name,email', 'freelancer:id,name,email', 'project:id,title', 'serviceRequest:id,title'])
            ->where('client_id', $userId)
            ->orWhere('freelancer_id', $userId)
            ->latest()
            ->get();

        return response()->json([
            'contracts' => $contracts,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $contract = Contract::with(['client:id,name,email', 'freelancer:id,name,email', 'project', 'serviceRequest', 'reviews'])
            ->findOrFail($id);

        if (! $this->canSee($request, $contract)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'contract' => $contract,
        ]);
    }

    public function companyContracts(Request $request)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Only company users can view company contracts'], 403);
        }

        $contracts = Contract::with([
            'client:id,name,email',
            'freelancer:id,name,email',
            'project:id,title',
            'serviceRequest:id,title',
            'jobPost:id,title',
        ])
            ->where('client_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return response()->json([
            'contracts' => $contracts,
        ]);
    }

    public function createCompanyJobContract(Request $request, int $jobId)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'Only company users can create job contracts'], 403);
        }

        $data = $request->validate([
            'freelancer_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $job = JobPost::with('company')->findOrFail($jobId);

        if (! $job->company || $job->company->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You cannot create a contract for a job you do not own'], 403);
        }

        $freelancer = User::where('role', 'personal')->findOrFail($data['freelancer_id']);
        $contract = $this->contractService->createFromJobPost($job, $freelancer, $data['amount']);

        return response()->json([
            'message' => 'Job contract created successfully.',
            'contract' => $contract->load(['client:id,name,email', 'freelancer:id,name,email', 'jobPost:id,title']),
        ], 201);
    }

    public function start(Request $request, int $id)
    {
        $contract = Contract::findOrFail($id);

        if ($contract->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Only client can start contract'], 403);
        }

        $contract = $this->contractService->fund($contract);

        return response()->json([
            'message' => 'Contract started and amount reserved.',
            'contract' => $contract,
        ]);
    }

    public function complete(Request $request, int $id)
    {
        $contract = Contract::findOrFail($id);

        if ($contract->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Only client can complete contract'], 403);
        }

        $contract = $this->contractService->complete($contract);

        return response()->json([
            'message' => 'Contract completed successfully.',
            'contract' => $contract,
        ]);
    }

    public function cancel(Request $request, int $id)
    {
        $contract = Contract::findOrFail($id);

        if (! $this->canSee($request, $contract)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $contract = $this->contractService->cancel($contract);

        return response()->json([
            'message' => 'Contract canceled successfully.',
            'contract' => $contract,
        ]);
    }

    private function canSee(Request $request, Contract $contract): bool
    {
        $user = $request->user();

        return $user->role === 'admin'
            || $contract->client_id === $user->id
            || $contract->freelancer_id === $user->id;
    }
}
