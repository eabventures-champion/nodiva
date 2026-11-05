<?php

declare(strict_types=1);

namespace App\Http\Controllers\Operations;

use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Actor;
use App\Domain\Organizations\CurrentOrganization;
use App\Http\Controllers\Controller;
use App\Models\CommunicationMessage;
use App\Models\CommunicationThread;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommunicationController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $current,
        private readonly AuditLogger $audit,
    ) {
    }

    public function index(Request $request)
    {
        $org = $this->current->organization();
        $user = auth()->user();

        $statusFilter = $request->query('status', 'open');
        $typeFilter = $request->query('type');
        $priorityFilter = $request->query('priority');
        $searchQuery = $request->query('q');

        $query = CommunicationThread::with([
            'senderOrganization',
            'recipientOrganization',
            'senderUser',
            'latestMessage.organization',
            'latestMessage.user',
        ]);

        if ($statusFilter && in_array($statusFilter, ['open', 'closed'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($typeFilter) {
            if ($typeFilter === 'broadcast') {
                $query->whereIn('recipient_type', ['all_hospitals', 'all_laboratories', 'all_corporates', 'all_partners']);
            } elseif (in_array($typeFilter, ['hospital', 'laboratory', 'corporate'], true)) {
                $query->where(function ($q) use ($typeFilter) {
                    $q->whereHas('recipientOrganization', fn ($ro) => $ro->where('type', $typeFilter))
                      ->orWhereHas('senderOrganization', fn ($so) => $so->where('type', $typeFilter))
                      ->orWhere('recipient_type', 'all_' . ($typeFilter === 'hospital' ? 'hospitals' : ($typeFilter === 'laboratory' ? 'laboratories' : 'corporates')));
                });
            }
        }

        if ($priorityFilter && in_array($priorityFilter, ['normal', 'high', 'urgent'], true)) {
            $query->where('priority', $priorityFilter);
        }

        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->where('subject', 'like', "%{$searchQuery}%")
                  ->orWhere('reference', 'like', "%{$searchQuery}%")
                  ->orWhereHas('recipientOrganization', fn ($ro) => $ro->where('name', 'like', "%{$searchQuery}%"))
                  ->orWhereHas('senderOrganization', fn ($so) => $so->where('name', 'like', "%{$searchQuery}%"));
            });
        }

        $threads = $query->orderByDesc('last_message_at')->paginate(15)->withQueryString();

        $openCount = CommunicationThread::where('status', 'open')->count();
        $closedCount = CommunicationThread::where('status', 'closed')->count();
        $broadcastCount = CommunicationThread::whereIn('recipient_type', ['all_hospitals', 'all_laboratories', 'all_corporates', 'all_partners'])->count();
        $urgentCount = CommunicationThread::where('status', 'open')->where('priority', 'urgent')->count();

        return view('operations.communications.index', [
            'organization' => $org,
            'threads' => $threads,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'priorityFilter' => $priorityFilter,
            'searchQuery' => $searchQuery,
            'openCount' => $openCount,
            'closedCount' => $closedCount,
            'broadcastCount' => $broadcastCount,
            'urgentCount' => $urgentCount,
        ]);
    }

    public function create(Request $request)
    {
        $org = $this->current->organization();

        $hospitals = Organization::where('type', 'hospital')->where('status', 'active')->orderBy('name')->get();
        $laboratories = Organization::where('type', 'laboratory')->where('status', 'active')->orderBy('name')->get();
        $corporates = Organization::where('type', 'corporate')->where('status', 'active')->orderBy('name')->get();

        $preselectedPartnerId = $request->query('partner_id');

        return view('operations.communications.create', [
            'organization' => $org,
            'hospitals' => $hospitals,
            'laboratories' => $laboratories,
            'corporates' => $corporates,
            'preselectedPartnerId' => $preselectedPartnerId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_type' => ['required', 'string', 'in:direct,all_hospitals,all_laboratories,all_corporates,all_partners'],
            'recipient_organization_id' => ['nullable', 'required_if:recipient_type,direct', 'exists:organizations,id'],
            'category' => ['required', 'string', 'in:general,operational,clinical,billing,compliance,urgent_notice'],
            'priority' => ['required', 'string', 'in:normal,high,urgent'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:3', 'max:15000'],
        ]);

        $org = $this->current->organization();
        $user = auth()->user();

        $thread = DB::transaction(function () use ($validated, $org, $user) {
            $thread = CommunicationThread::create([
                'subject' => $validated['subject'],
                'category' => $validated['category'],
                'priority' => $validated['priority'],
                'recipient_type' => $validated['recipient_type'],
                'sender_organization_id' => $org->id,
                'sender_user_id' => $user->id,
                'recipient_organization_id' => $validated['recipient_type'] === 'direct' ? (int) $validated['recipient_organization_id'] : null,
                'status' => 'open',
                'last_message_at' => now(),
            ]);

            $message = CommunicationMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'message' => $validated['message'],
            ]);

            $thread->markAsReadFor($user);

            return $thread;
        });

        $this->audit->log(
            'communication.dispatched',
            $thread,
            [
                'reference' => $thread->reference,
                'subject' => $thread->subject,
                'recipient_type' => $thread->recipient_type,
                'priority' => $thread->priority,
            ],
            'success',
            $this->current->actor()
        );

        return redirect()->route('ops.communications.show', $thread->uuid)
            ->with('success', "Communication {$thread->reference} dispatched successfully.");
    }

    public function show(CommunicationThread $thread)
    {
        $org = $this->current->organization();
        $user = auth()->user();

        $thread->load([
            'senderOrganization',
            'recipientOrganization',
            'senderUser',
            'closer',
            'messages.user',
            'messages.organization',
        ]);

        $thread->markAsReadFor($user);

        return view('operations.communications.show', [
            'organization' => $org,
            'thread' => $thread,
        ]);
    }

    public function reply(Request $request, CommunicationThread $thread)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:15000'],
        ]);

        $org = $this->current->organization();
        $user = auth()->user();

        DB::transaction(function () use ($thread, $validated, $org, $user) {
            $message = CommunicationMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'message' => $validated['message'],
            ]);

            $thread->update([
                'last_message_at' => now(),
                'status' => 'open',
                'closed_at' => null,
                'closed_by' => null,
            ]);

            $thread->markAsReadFor($user);
        });

        $this->audit->log(
            'communication.reply',
            $thread,
            [
                'reference' => $thread->reference,
            ],
            'success',
            $this->current->actor()
        );

        return redirect()->route('ops.communications.show', $thread->uuid)
            ->with('success', 'Your reply has been sent.');
    }

    public function toggleStatus(CommunicationThread $thread)
    {
        $user = auth()->user();

        if ($thread->status === 'open') {
            $thread->update([
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $user->id,
            ]);
            $msg = "Communication {$thread->reference} marked as closed / resolved.";
            $action = 'communication.closed';
        } else {
            $thread->update([
                'status' => 'open',
                'closed_at' => null,
                'closed_by' => null,
            ]);
            $msg = "Communication {$thread->reference} reopened.";
            $action = 'communication.reopened';
        }

        $this->audit->log(
            $action,
            $thread,
            ['reference' => $thread->reference, 'status' => $thread->status],
            'success',
            $this->current->actor()
        );

        return redirect()->back()->with('success', $msg);
    }
}
