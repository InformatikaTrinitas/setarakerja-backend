<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $skills = Skill::where('user_id', $request->user()->id)
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Skill $skill) => $skill->toApi());

        return response()->json(['skills' => $skills]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
            'score' => ['required', 'integer', 'min:0', 'max:100'],
        ], [
            'name.required' => 'Nama skill wajib diisi.',
            'name.min' => 'Nama skill minimal 2 karakter.',
            'name.max' => 'Nama skill maksimal 60 karakter.',
            'score.required' => 'Skor wajib diisi.',
            'score.integer' => 'Skor harus berupa angka.',
            'score.min' => 'Skor minimal 0.',
            'score.max' => 'Skor maksimal 100.',
        ]);

        $name = trim($data['name']);

        $duplicate = Skill::where('user_id', $request->user()->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($duplicate) {
            return response()->json([
                'message' => 'Skill ini sudah ada di passport kamu.',
                'errors' => ['name' => ['Skill ini sudah ada di passport kamu.']],
            ], 422);
        }

        $skill = Skill::create([
            'user_id' => $request->user()->id,
            'name' => $name,
            'score' => (int) $data['score'],
            'verified_at' => null,
        ]);

        return response()->json(['skill' => $skill->toApi()], 201);
    }

    public function update(Request $request, Skill $skill): JsonResponse
    {
        $this->authorizeOwner($request, $skill);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'min:2', 'max:60'],
            'score' => ['sometimes', 'integer', 'min:0', 'max:100'],
        ], [
            'name.min' => 'Nama skill minimal 2 karakter.',
            'name.max' => 'Nama skill maksimal 60 karakter.',
            'score.integer' => 'Skor harus berupa angka.',
            'score.min' => 'Skor minimal 0.',
            'score.max' => 'Skor maksimal 100.',
        ]);

        if (isset($data['name'])) {
            $skill->name = trim($data['name']);
        }
        if (isset($data['score'])) {
            $skill->score = (int) $data['score'];
        }

        $skill->save();

        return response()->json(['skill' => $skill->toApi()]);
    }

    public function verify(Request $request, Skill $skill): JsonResponse
    {
        $this->authorizeOwner($request, $skill);

        if ($skill->verified_at === null) {
            $skill->update(['verified_at' => now()]);
        }

        return response()->json(['skill' => $skill->fresh()->toApi()]);
    }

    public function destroy(Request $request, Skill $skill): JsonResponse
    {
        $this->authorizeOwner($request, $skill);

        $skill->delete();

        return response()->json(['message' => 'Skill dihapus.']);
    }

    private function authorizeOwner(Request $request, Skill $skill): void
    {
        abort_if($skill->user_id !== $request->user()->id, 403, 'Bukan milik Anda.');
    }
}
