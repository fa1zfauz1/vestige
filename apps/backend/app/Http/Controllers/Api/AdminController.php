<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Photo;
use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function pendingUsers()
    {
        return User::where('status', 'pending')->get();
    }

    public function approve(User $user)
    {
        $user->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'appeal_reason' => null,
        ]);

        AuditLog::record('user.approved', $user, 'Member approved');

        return response()->json($user);
    }

    public function reject(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $user->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
            'appeal_reason' => null,
        ]);

        AuditLog::record('user.rejected', $user, 'Rejected: ' . $validated['reason']);

        return response()->json($user);
    }

    public function suspend(Request $request, User $user)
    {
        $this->guardNotSelf($request, $user);

        $user->update(['status' => 'suspended']);

        AuditLog::record('user.suspended', $user, 'Member suspended');

        return response()->json($user);
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,family_member',
        ]);

        $isSelf = $request->user()->id === $user->id;

        if ($isSelf && $validated['role'] !== 'admin') {
            return response()->json(['message' => 'You cannot remove admin from your own account'], 422);
        }

        if ($validated['role'] === 'family_member' && $user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->where('status', 'approved')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'At least one active admin is required'], 422);
            }
        }

        $user->update(['role' => $validated['role']]);

        AuditLog::record('user.role_changed', $user, 'Role changed to ' . $validated['role']);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user)
    {
        $this->guardNotSelf($request, $user);
        $this->guardLastAdmin($user);

        foreach ($user->photos as $photo) {
            Storage::disk('minio')->delete($photo->front_image_path);
            if ($photo->back_image_path) {
                Storage::disk('minio')->delete($photo->back_image_path);
            }
            $photo->delete();
        }

        AuditLog::record('user.deleted', $user, 'User deleted', $request->user());

        $user->tokens()->delete();
        $user->delete();

        return response()->json(null, 204);
    }

    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with('actor:id,name')
            ->latest('id');

        if ($request->filled('action')) {
            $query->where('action', $request->query('action'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        return $query->paginate(50)->through(function (ActivityLog $log) {
            $subjectName = null;
            if ($log->subject) {
                $subjectName = $log->subject->name ?? $log->subject->title ?? null;
            }

            return [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => $log->created_at,
                'actor' => $log->actor?->name,
                'target' => $subjectName,
                'target_type' => $log->subject_type ? class_basename($log->subject_type) : null,
            ];
        });
    }

    private function guardNotSelf(Request $request, User $user)
    {
        if ($request->user()->id === $user->id) {
            abort(422, 'You cannot perform this action on your own account');
        }
    }

    private function guardLastAdmin(User $user)
    {
        if ($user->role === 'admin' && $user->status === 'approved') {
            $adminCount = User::where('role', 'admin')->where('status', 'approved')->count();
            if ($adminCount <= 1) {
                abort(422, 'At least one active admin is required');
            }
        }
    }

    public function users()
    {
        return User::all();
    }
}
