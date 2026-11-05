<?php

declare(strict_types=1);

namespace App\Http\Controllers\Hospital;

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

        $query = CommunicationThread::with([
            'senderOrganization',
            'recipientOrganization',
            'senderUser',
            'latestMessage.organization',
            'latestMessage.user',
        ])
        ->where(function ($q) use ($org) {
            $q->where('recipient_organization_id', $org->id)
              ->orWhere('sender_organization_id', $org->id)
              ->orWhere('recipient_type', 'all_hospitals')
              ->orWhere('recipient_type', 'all_partners');
        });

        $statusFilter = $request->query('status', 'all');
        if ($statusFilter && in_array($statusFilter, ['open', 'closed'], true)) {
            $query->where('status', $statusFilter);
        }

        $threads = $query->orderByDesc('last_message_at')->paginate(15)->withQueryString();

        return view('hospital.communications.index', [
            'organization' => $org,
            'threads' => $threads,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function create(Request $request)
    {
        $org = $this->current->organization();
        $platformOrg = Organization::where('type', 'platform')->first();
        $laboratories = Organization::where('type', 'laboratory')->where('status', 'active')->orderBy('name')->get();
        $corporates = Organization::where('type', 'corporate')->where('status', 'active')->orderBy('name')->get();
        $hospitals = Organization::where('type', 'hospital')->where('status', 'active')->where('id', '!=', $org->id)->orderBy('name')->get();

        $preselectedPartnerId = $request->query('partner_id');

        return view('hospital.communications.create', [
            'organization' => $org,
            'platformOrg' => $platformOrg,
            'laboratories' => $laboratories,
            'corporates' => $corporates,
            'hospitals' => $hospitals,
            'preselectedPartnerId' => $preselectedPartnerId,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'recipient_organization_id' => ['required', 'exists:organizations,id'],
            'category' => ['required', 'string', 'in:general,operational,clinical,billing,urgent_notice'],
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
                'recipient_type' => 'direct',
                'sender_organization_id' => $org->id,
                'sender_user_id' => $user->id,
                'recipient_organization_id' => (int) $validated['recipient_organization_id'],
                'status' => 'open',
                'last_message_at' => now(),
            ]);

            CommunicationMessage::create([
                'thread_id' => $thread->id,
                'user_id' => $user->id,
                'organization_id' => $org->id,
                'message' => $validated['message'],
            ]);

            $thread->markAsReadFor($user);

            return $thread;
        });

        $this->audit->log(
            'communication.sent',
            $thread,
            [
                'reference' => $thread->reference,
                'recipient_organization_id' => $thread->recipient_organization_id,
            ],
            'success',
            $this->current->actor()
        );

        return redirect()->route('hospital.communications.show', $thread->uuid)
            ->with('success', "Message {$thread->reference} sent successfully.");
    }

    public function show(CommunicationThread $thread)
    {
        $org = $this->current->organization();
        $user = auth()->user();

        abort_unless($thread->isAccessibleBy($org), 403, 'Unauthorized access to this communication thread.');

        $thread->load([
            'senderOrganization',
            'recipientOrganization',
            'senderUser',
            'closer',
            'messages.user',
            'messages.organization',
        ]);

        $thread->markAsReadFor($user);

        return view('hospital.communications.show', [
            'organization' => $org,
            'thread' => $thread,
        ]);
    }

    public function reply(Request $request, CommunicationThread $thread)
    {
        $org = $this->current->organization();
        $user = auth()->user();

        abort_unless($thread->isAccessibleBy($org), 403, 'Unauthorized access to this communication thread.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2', 'max:15000'],
        ]);

        $wasClosed = $thread->status === 'closed';

        DB::transaction(function () use ($thread, $validated, $org, $user) {
            CommunicationMessage::create([
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
            ['reference' => $thread->reference, 'reopened' => $wasClosed],
            'success',
            $this->current->actor()
        );

        $successMessage = $wasClosed 
            ? 'Your reply has been sent and the thread has been reopened.' 
            : 'Your reply has been dispatched successfully.';

        return redirect()->route('hospital.communications.show', $thread->uuid)
            ->with('success', $successMessage);
    }

    public function toggleStatus(CommunicationThread $thread)
    {
        $org = $this->current->organization();
        $user = auth()->user();

        abort_unless($thread->canBeManagedBy($org), 403, 'Unauthorized to change status of this communication thread.');

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
