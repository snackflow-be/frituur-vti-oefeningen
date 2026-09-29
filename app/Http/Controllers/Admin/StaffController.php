<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffPasswordRequest;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $staff = User::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('admin/staff/index', [
            'staff' => $staff,
            'currentUserId' => $request->user()?->id,
        ]);
    }

    /**
     * Uitnodigen = aanmaken met tijdelijk wachtwoord; de baas geeft het mondeling door (B-13).
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        $user = new User([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$user->name} is toegevoegd als personeel."]);

        return to_route('admin.staff.index');
    }

    public function password(StaffPasswordRequest $request, User $user): RedirectResponse
    {
        $user->password = Hash::make($request->string('password')->toString());
        $user->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => "Nieuw wachtwoord voor {$user->name} bewaard."]);

        return to_route('admin.staff.index');
    }

    /**
     * Niet jezelf en niet de laatste (B-14).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            throw ValidationException::withMessages([
                'staff' => ['Je kunt jezelf niet verwijderen.'],
            ]);
        }

        if (User::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'staff' => ['Het laatste personeelslid kan niet weg.'],
            ]);
        }

        $name = $user->name;
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => "{$name} is verwijderd."]);

        return to_route('admin.staff.index');
    }
}
