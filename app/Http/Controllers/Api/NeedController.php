<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Need;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $needs = Need::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Need $need) => $need->toApi());

        return response()->json(['needs' => $needs]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'desc' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string', 'max:100'],
            'priority' => ['required', 'in:Tinggi,Sedang,Rendah'],
            'status' => ['sometimes', 'in:dibutuhkan,diproses,terpenuhi'],
        ]);

        $need = Need::create([
            'user_id' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['desc'] ?? null,
            'category' => $data['category'],
            'priority' => $data['priority'],
            'status' => $data['status'] ?? 'dibutuhkan',
        ]);

        return response()->json(['need' => $need->toApi()], 201);
    }

    public function update(Request $request, Need $need): JsonResponse
    {
        $this->authorizeOwner($request, $need);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'desc' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'category' => ['sometimes', 'string', 'max:100'],
            'priority' => ['sometimes', 'in:Tinggi,Sedang,Rendah'],
            'status' => ['sometimes', 'in:dibutuhkan,diproses,terpenuhi'],
        ]);

        if (array_key_exists('title', $data)) {
            $need->title = $data['title'];
        }
        if (array_key_exists('desc', $data)) {
            $need->description = $data['desc'];
        }
        if (array_key_exists('category', $data)) {
            $need->category = $data['category'];
        }
        if (array_key_exists('priority', $data)) {
            $need->priority = $data['priority'];
        }
        if (array_key_exists('status', $data)) {
            $need->status = $data['status'];
        }

        $need->save();

        return response()->json(['need' => $need->toApi()]);
    }

    public function destroy(Request $request, Need $need): JsonResponse
    {
        $this->authorizeOwner($request, $need);

        $need->delete();

        return response()->json(['message' => 'Kebutuhan dihapus.']);
    }

    private function authorizeOwner(Request $request, Need $need): void
    {
        abort_if($need->user_id !== $request->user()->id, 403, 'Bukan milik Anda.');
    }
}
