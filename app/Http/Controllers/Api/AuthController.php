<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'role' => ['required', 'in:kandidat,hrd,admin'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'fullName' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'disabilityType' => ['nullable', 'in:tunarungu,tunadaksa,tunanetra,tunawicara,autisme,lainnya'],
            'companyName' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'agreeTerms' => ['accepted'],
        ]);

        $user = User::create([
            'name' => $data['fullName'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'title' => $data['title'] ?? ($data['role'] === 'hrd' ? 'Admin Officer' : 'Software Engineer'),
            'disability_type' => $data['disabilityType'] ?? null,
            'company_name' => $data['companyName'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => $user->toApi(),
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau kata sandi salah.'],
            ]);
        }

        return response()->json([
            'token' => $user->createToken('spa')->plainTextToken,
            'user' => $user->toApi(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()->toApi()]);
    }
}
