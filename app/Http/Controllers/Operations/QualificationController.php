<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\CredentialCategory;
use App\Models\LaboratoryQualification;
use App\Models\Organization;
use App\Models\QualificationVerification;
use Illuminate\Http\Request;

class QualificationController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $statusFilter = $request->query('status');
        $searchQuery = $request->query('q');

        $laboratoriesQuery = Organization::where('type', 'laboratory')
            ->with(['region', 'qualifications' => function ($q) use ($statusFilter, $searchQuery) {
                $q->with(['verifier', 'verifications.actor'])->latest('id');
                if ($statusFilter && in_array($statusFilter, ['pending', 'verified', 'rejected', 'expired'])) {
                    $q->where('status', $statusFilter);
                }
                if ($searchQuery) {
                    $q->where(function ($sub) use ($searchQuery) {
                        $sub->where('reference_number', 'like', "%{$searchQuery}%")
                            ->orWhere('issuing_body', 'like', "%{$searchQuery}%")
                            ->orWhere('scope', 'like', "%{$searchQuery}%");
                    });
                }
            }])
            ->when($statusFilter, function ($q) use ($statusFilter) {
                $q->whereHas('qualifications', function ($sub) use ($statusFilter) {
                    $sub->where('status', $statusFilter);
                });
            })
            ->when($searchQuery, function ($q) use ($searchQuery) {
                $q->where(function ($sub) use ($searchQuery) {
                    $sub->where('name', 'like', "%{$searchQuery}%")
                        ->orWhere('city', 'like', "%{$searchQuery}%")
                        ->orWhereHas('qualifications', function ($subQ) use ($searchQuery) {
                            $subQ->where('reference_number', 'like', "%{$searchQuery}%")
                                 ->orWhere('issuing_body', 'like', "%{$searchQuery}%")
                                 ->orWhere('scope', 'like', "%{$searchQuery}%");
                        });
                });
            })
            ->orderBy('name');

        $laboratories = $laboratoriesQuery->paginate(10)->withQueryString();

        // Credential counts for filter badges
        $totalCount = LaboratoryQualification::count();
        $verifiedCount = LaboratoryQualification::where('status', 'verified')->count();
        $pendingCount = LaboratoryQualification::where('status', 'pending')->count();
        $expiredCount = LaboratoryQualification::where('status', 'expired')->count();

        $categories = CredentialCategory::orderBy('sort_order')->get();
        $compulsoryCount = $categories->where('is_compulsory', true)->where('is_active', true)->count();

        return view('operations.qualifications', [
            'organization' => $org,
            'laboratories' => $laboratories,
            'categories' => $categories,
            'compulsoryCount' => $compulsoryCount,
            'currentStatus' => $statusFilter,
            'searchQuery' => $searchQuery,
            'totalCount' => $totalCount,
            'verifiedCount' => $verifiedCount,
            'pendingCount' => $pendingCount,
            'expiredCount' => $expiredCount,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'alpha_dash', 'unique:credential_categories,code'],
            'name' => ['required', 'string', 'max:150'],
            'issuing_authority_hint' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_compulsory' => ['nullable', 'boolean'],
        ]);

        CredentialCategory::create([
            'code' => strtolower($validated['code']),
            'name' => $validated['name'],
            'issuing_authority_hint' => $validated['issuing_authority_hint'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_compulsory' => $request->boolean('is_compulsory'),
            'is_active' => true,
            'sort_order' => (CredentialCategory::max('sort_order') ?? 0) + 1,
        ]);

        return back()->with('success', "Credential category '{$validated['name']}' created successfully.");
    }

    public function updateCategory(Request $request, CredentialCategory $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'issuing_authority_hint' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_compulsory' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'issuing_authority_hint' => $validated['issuing_authority_hint'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_compulsory' => $request->boolean('is_compulsory'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', "Credential category '{$category->name}' updated successfully.");
    }

    public function toggleCompulsory(CredentialCategory $category)
    {
        $category->is_compulsory = ! $category->is_compulsory;
        $category->save();

        $status = $category->is_compulsory ? 'Compulsory (Mandatory for Lab Approval)' : 'Optional / Recommended';
        return back()->with('success', "Category '{$category->name}' is now {$status}.");
    }

    public function toggleActive(CredentialCategory $category)
    {
        $category->is_active = ! $category->is_active;
        $category->save();

        $status = $category->is_active ? 'Active' : 'Disabled';
        return back()->with('success', "Category '{$category->name}' is now {$status}.");
    }

    public function verify(Request $request, LaboratoryQualification $qualification)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:verified,rejected'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $qualification->status = $validated['action'];
        $qualification->verified_by = auth()->id();
        $qualification->verified_at = now();
        $qualification->save();

        QualificationVerification::create([
            'laboratory_qualification_id' => $qualification->id,
            'action' => $validated['action'],
            'notes' => $validated['notes'] ?? 'Status updated by platform operations.',
            'actor_id' => auth()->id(),
        ]);

        return back()->with('success', "Qualification updated to {$validated['action']}.");
    }

    public function document(LaboratoryQualification $qualification)
    {
        if (! $qualification->document_path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($qualification->document_path)) {
            abort(404, 'Document file not found on server.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->response(
            $qualification->document_path,
            $qualification->document_name ?? 'qualification-document'
        );
    }
}
