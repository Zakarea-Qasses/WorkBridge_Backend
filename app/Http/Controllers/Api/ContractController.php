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
            ->where(function ($query) use ($userId) {
                $query->where('client_id', $userId)
                    ->orWhere('freelancer_id', $userId);
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'contracts' => $contracts,
        ]);
    }

    public function show(Request $request, int $id)
    {
        $contract = Contract::with(['client:id,name,email', 'freelancer:id,name,email', 'project', 'serviceRequest', 'reviews'])
            ->findOrFail($id);

        if (! $this->canSee($request, $contract)) {
            return response()->json(['message' => 'غير مصرح لك بتنفيذ هذا الإجراء'], 403);
        }

        return response()->json([
            'contract' => $contract,
        ]);
    }

    public function companyContracts(Request $request)
    {
        if ($request->user()->role !== 'company') {
            return response()->json(['message' => 'فقط حسابات الشركات يمكنها عرض عقود الشركة'], 403);
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
            return response()->json(['message' => 'فقط حسابات الشركات يمكنها إنشاء عقود الوظائف'], 403);
        }

        $data = $request->validate([
            'freelancer_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $job = JobPost::with('company')->findOrFail($jobId);

        if (! $job->company || $job->company->user_id !== $request->user()->id) {
            return response()->json(['message' => 'لا يمكنك إنشاء عقد لوظيفة لا تملكها'], 403);
        }

        $freelancer = User::where('role', 'personal')->findOrFail($data['freelancer_id']);
        $contract = $this->contractService->createFromJobPost($job, $freelancer, $data['amount']);

        return response()->json([
            'message' => 'تم إنشاء عقد الوظيفة بنجاح.',
            'contract' => $contract->load(['client:id,name,email', 'freelancer:id,name,email', 'jobPost:id,title']),
        ], 201);
    }

    public function start(Request $request, int $id)
    {
        $contract = Contract::findOrFail($id);

        if ($contract->client_id !== $request->user()->id) {
            return response()->json(['message' => 'فقط صاحب العقد يمكنه بدء العقد'], 403);
        }

        $contract = $this->contractService->fund($contract);

        return response()->json([
            'message' => 'تم بدء العقد وحجز المبلغ بنجاح.',
            'contract' => $contract,
        ]);
    }

    public function complete(Request $request, int $id)
    {
        $contract = Contract::findOrFail($id);

        if ($contract->client_id !== $request->user()->id) {
            return response()->json(['message' => 'فقط صاحب العقد يمكنه إكمال العقد'], 403);
        }

        $contract = $this->contractService->complete($contract);

        return response()->json([
            'message' => 'تم إكمال العقد بنجاح.',
            'contract' => $contract,
        ]);
    }

    public function cancel(Request $request, int $id)
    {
        $contract = Contract::findOrFail($id);

        if (! $this->canSee($request, $contract)) {
            return response()->json(['message' => 'غير مصرح لك بتنفيذ هذا الإجراء'], 403);
        }

        $contract = $this->contractService->cancel($contract);

        return response()->json([
            'message' => 'تم إلغاء العقد بنجاح.',
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
