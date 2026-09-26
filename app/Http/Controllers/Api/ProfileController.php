<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json(['profile' => $request->user()->toApi()]);
    }

    /**
     * Upload foto profil (file, bukan base64). Disimpan ke storage/app/public/avatars.
     */
    public function photo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'file', 'image', 'max:2048'],
        ]);

        $user = $request->user();
        $file = $data['photo'];
        $name = 'user-'.$user->id.'-'.Str::lower(Str::random(10)).'.'.($file->getClientOriginalExtension() ?: 'jpg');
        $path = $file->storeAs('avatars', $name, 'public');

        $previous = $user->avatar;
        $user->avatar = '/storage/'.$path;
        $user->save();

        if (is_string($previous) && str_starts_with($previous, '/storage/avatars/')) {
            Storage::disk('public')->delete(substr($previous, strlen('/storage/')));
        }

        return response()->json(['profile' => $user->toApi()]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'title' => ['sometimes', 'string', 'max:255'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:3000000'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'disabilityType' => ['sometimes', 'nullable', 'in:tunarungu,tunadaksa,tunanetra,tunawicara,autisme,lainnya'],
            'companyName' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        if (array_key_exists('name', $data)) {
            $user->name = $data['name'];
        }
        if (array_key_exists('title', $data)) {
            $user->title = $data['title'];
        }
        if (array_key_exists('avatar', $data)) {
            $user->avatar = $data['avatar'];
        }
        if (array_key_exists('phone', $data)) {
            $user->phone = $data['phone'];
        }
        if (array_key_exists('disabilityType', $data)) {
            $user->disability_type = $data['disabilityType'];
        }
        if (array_key_exists('companyName', $data)) {
            $user->company_name = $data['companyName'];
        }

        $user->save();

        return response()->json(['profile' => $user->toApi()]);
    }
}
