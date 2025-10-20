<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * Show the profile edit form
     */
    public function edit()
    {
        $user = auth()->user();

        return view('patient.profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'nullable|in:male,female,other,prefer_not_to_say',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:255',
            'address_postcode' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:2',
        ]);

        try {
            // Build address array
            $address = null;
            if ($request->filled('address_line1')) {
                $address = [
                    'line1' => $request->address_line1,
                    'line2' => $request->address_line2,
                    'city' => $request->address_city,
                    'postcode' => $request->address_postcode,
                    'country' => $request->address_country ?? 'GB',
                ];
            }

            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'date_of_birth' => $validated['date_of_birth'],
                'gender' => $validated['gender'],
                'address' => $address,
            ]);

            activity()
                ->performedOn($user)
                ->log('Profile updated');

            Log::info('Patient profile updated', [
                'user_id' => $user->id,
            ]);

            return back()->with('success', 'Profile updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update patient profile', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Failed to update profile: ' . $e->getMessage());
        }
    }

    /**
     * Update the user's password
     */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'The provided password does not match your current password.',
            ]);
        }

        try {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            activity()
                ->performedOn($user)
                ->log('Password changed');

            Log::info('Patient password changed', [
                'user_id' => $user->id,
            ]);

            return back()->with('success', 'Password updated successfully');

        } catch (\Exception $e) {
            Log::error('Failed to update patient password', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to update password: ' . $e->getMessage());
        }
    }

    /**
     * Delete the user's account
     */
    public function destroy(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'password' => 'required|string',
        ]);

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors([
                'password' => 'The provided password is incorrect.',
            ]);
        }

        // Check if user has pending orders
        if ($user->orders()->whereIn('status', ['paid', 'scheduled', 'collected', 'sent_to_lab', 'processing'])->exists()) {
            return back()->with('error', 'Cannot delete account with pending orders. Please contact support.');
        }

        try {
            Log::warning('Patient account deleted', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            auth()->logout();

            $user->delete();

            return redirect('/')->with('success', 'Your account has been deleted successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to delete patient account', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to delete account: ' . $e->getMessage());
        }
    }
}