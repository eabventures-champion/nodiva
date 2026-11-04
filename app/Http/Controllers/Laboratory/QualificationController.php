<?php

declare(strict_types=1);

namespace App\Http\Controllers\Laboratory;

use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\LaboratoryQualification;
use Illuminate\Http\Request;

class QualificationController extends Controller
{
    public function __construct(private readonly CurrentOrganization $current)
    {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();

        $qualifications = LaboratoryQualification::where('organization_id', $org->id)
            ->with(['verifier'])
            ->latest('id')
            ->get();

        $categories = \App\Models\CredentialCategory::where('is_active', true)->orderBy('sort_order')->get();
        $compulsoryStatus = $org->compulsoryQualificationsStatus();

        $metrics = [
            'total' => $qualifications->count(),
            'verified' => $qualifications->where('status', 'verified')->count(),
            'pending' => $qualifications->where('status', 'pending')->count(),
        ];

        return view('laboratory.qualifications.index', [
            'organization' => $org,
            'qualifications' => $qualifications,
            'categories' => $categories,
            'compulsoryStatus' => $compulsoryStatus,
            'metrics' => $metrics,
        ]);
    }

    public function store(Request $request)
    {
        $org = $this->current->organization();

        $validated = $request->validate([
            'type' => ['required', 'string', 'exists:credential_categories,code'],
            'reference_number' => ['required', 'string', 'max:100'],
            'issuing_body' => ['required', 'string', 'max:150'],
            'scope' => ['required', 'string', 'max:255'],
            'issued_on' => ['required', 'date'],
            'expires_on' => ['nullable', 'date', 'after:issued_on'],
            'document' => ['required', 'file', 'mimes:pdf,png,jpg,jpeg', 'max:10240'],
        ]);

        $docPath = null;
        $docName = null;
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $docPath = $file->store('qualifications', 'local');
            $docName = $file->getClientOriginalName();
        }

        LaboratoryQualification::create([
            'organization_id' => $org->id,
            'type' => $validated['type'],
            'reference_number' => $validated['reference_number'],
            'issuing_body' => $validated['issuing_body'],
            'scope' => $validated['scope'],
            'issued_on' => $validated['issued_on'],
            'expires_on' => $validated['expires_on'] ?? null,
            'document_path' => $docPath,
            'document_name' => $docName,
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Licensing credential and document submitted for NODIVA Operations verification.');
    }

    public function document(LaboratoryQualification $qualification)
    {
        $org = $this->current->organization();
        if ($qualification->organization_id !== $org->id) {
            abort(403);
        }

        if (! $qualification->document_path || ! \Illuminate\Support\Facades\Storage::disk('local')->exists($qualification->document_path)) {
            abort(404, 'Document file not found.');
        }

        return \Illuminate\Support\Facades\Storage::disk('local')->response(
            $qualification->document_path,
            $qualification->document_name ?? 'qualification-document'
        );
    }
}
