<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name'          => 'sometimes|required|string|max:255',
            'tanggal_lahir' => 'sometimes|nullable|date|before:today',
            'jenis_kelamin' => 'sometimes|nullable|in:L,P',
            'lat'           => 'sometimes|nullable|numeric|between:-90,90',
            'lng'           => 'sometimes|nullable|numeric|between:-180,180',
        ]);

        $user->update($data);

        return response()->json(['message' => 'Profil diperbarui.', 'data' => [
            'id' => $user->id, 'name' => $user->name,
            'tanggal_lahir' => $user->tanggal_lahir?->format('Y-m-d'),
            'jenis_kelamin' => $user->jenis_kelamin, 'lat' => $user->lat, 'lng' => $user->lng,
        ]]);
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'password_lama' => 'required|string',
            'password'      => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();
        if (! Hash::check($data['password_lama'], $user->password)) {
            return response()->json(['message' => 'Kata sandi lama salah.'], 422);
        }

        $user->update(['password' => Hash::make($data['password'])]);

        return response()->json(['message' => 'Kata sandi diperbarui.']);
    }
}