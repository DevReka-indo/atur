<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'email'        => 'required|email',
            'type'         => 'required|in:workspace,project',
            'invitable_id' => 'required|integer',
        ]);

        $exists = Invitation::where('email', $request->email)
            ->where('type', $request->type)
            ->where('invitable_id', $request->invitable_id)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            return back()->with('invite_error', 'This email has already been invited.');
        }

        $invitable = $request->type === 'workspace'
            ? Workspace::findOrFail($request->invitable_id)
            : Project::findOrFail($request->invitable_id);

        $invitation = Invitation::create([
            'email'        => $request->email,
            'token'        => Str::uuid(),
            'type'         => $request->type,
            'invitable_id' => $request->invitable_id,
            'invited_by' => Auth::id(),
            'status'       => 'pending',
            'expires_at'   => now()->addDays(3),
        ]);

        Mail::to($request->email)->send(new InvitationMail($invitation, $invitable->name));

        return back()->with('invite_success', 'Invitation sent to ' . $request->email);
    }

    public function accept(string $token)
    {
        $invitation = Invitation::where('token', $token)
            ->where('status', 'pending')
            ->firstOrFail();

        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            return redirect()->route('login')->with('error', 'Invitation has expired.');
        }

        $invitable = $invitation->type === 'workspace'
            ? \App\Models\Workspace::find($invitation->invitable_id)
            : \App\Models\Project::find($invitation->invitable_id);

        return view('invitations.index', compact('invitation', 'invitable'));
    }

    public function complete()
    {
        $token = session('invitation_token');
        if (!$token) return;

        $invitation = Invitation::where('token', $token)->where('status', 'pending')->first();
        if (!$invitation || $invitation->isExpired()) return;

        $user = auth::user();

        if ($invitation->type === 'workspace') {
            Workspace::find($invitation->invitable_id)?->members()->syncWithoutDetaching($user->id);
        } else {
            Project::find($invitation->invitable_id)?->members()->syncWithoutDetaching($user->id);
        }

        $invitation->update(['status' => 'accepted']);
        session()->forget('invitation_token');
    }

    public function join(Request $request)
    {
        $token = session('invitation_token');
        if (!$token) return redirect()->route('dashboard');

        $invitation = Invitation::where('token', $token)->where('status', 'pending')->firstOrFail();

        if ($invitation->isExpired()) {
            $invitation->update(['status' => 'expired']);
            session()->forget('invitation_token');
            return redirect()->route('dashboard')->with('error', 'Invitation has expired.');
        }

        $user = auth::User();

        if ($invitation->type === 'workspace') {
            Workspace::find($invitation->invitable_id)?->members()->syncWithoutDetaching($user->id);
        } else {
            Project::find($invitation->invitable_id)?->members()->syncWithoutDetaching($user->id);
        }

        $invitation->update(['status' => 'accepted']);
        session()->forget('invitation_token');

        return redirect()->route('dashboard')->with('success', 'You have successfully joined!');
    }

    public function reject(Request $request)
    {
        session()->forget('invitation_token');
        return redirect()->route('dashboard')->with('info', 'Invitation rejected.');
    }

    public function storeSession(Request $request)
    {
        session(['invitation_token' => $request->token]);

        $redirect = $request->redirect === 'register' ? 'register' : 'login';
        return redirect()->route($redirect);
    }

    public function decline(Request $request)
    {
        $invitation = Invitation::where('token', $request->token)->first();
        if ($invitation) {
            $invitation->update(['status' => 'expired']);
        }
        return redirect()->route('login')->with('info', 'Invitation declined.');
    }

    public function joinViaLink(string $token)
    {
        $workspace = Workspace::where('invite_token', $token)->firstOrFail();

        if (!auth()->check()) {
            session(['workspace_invite_token' => $token]);
            return redirect()->route('login', ['invite_token' => $token])
                ->with('info', 'Silakan login terlebih dahulu untuk bergabung.');
        }

        $user = auth()->user();

        if ($workspace->isMember($user) || $workspace->isOwner($user)) {
            return redirect()->route('workspaces.show', $workspace)
                ->with('info', 'Kamu sudah menjadi member workspace ini.');
        }

        return view('invitations.confirm', compact('workspace', 'token'));
    }

    public function acceptViaLink(Request $request, string $token)
    {
        $workspace = Workspace::where('token', $token)->firstOrFail();

        if ($workspace->invite_token !== $request->input('token')) {
            abort(403, 'Invalid invite token.');
        }

        $user = auth()->user();

        if (!$workspace->isMember($user) && !$workspace->isOwner($user)) {
            $workspace->members()->syncWithoutDetaching([
                $user->id => ['role' => 'member']
            ]);
        }

        session()->forget('workspace_invite_token');

        return redirect()->route('workspaces.show', $workspace->token)
            ->with('success', 'Selamat datang di workspace ' . $workspace->name . '!');
    }

    public function declineViaLink(Request $request, string $token)
    {
        session()->forget('workspace_invite_token');
        return redirect()->route('dashboard')
            ->with('info', 'Kamu menolak undangan workspace.');
    }
}
