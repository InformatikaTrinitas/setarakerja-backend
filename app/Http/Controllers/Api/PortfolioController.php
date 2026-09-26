<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortfolioController extends Controller
{
    private const ALLOWED_MIME = [
        'application/pdf',
        'application/zip',
        'application/x-zip-compressed',
        'image/png',
        'image/jpeg',
        'image/webp',
    ];

    public function index(Request $request): JsonResponse
    {
        $files = Portfolio::where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Portfolio $file) => $file->toApi());

        return response()->json(['files' => $files]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200', 'mimes:pdf,zip,png,jpg,jpeg,webp'],
        ]);

        $file = $request->file('file');

        if (! in_array($file->getMimeType(), self::ALLOWED_MIME, true)) {
            return response()->json(['message' => 'Tipe file tidak didukung.'], 422);
        }

        $stored = Str::uuid()->toString().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs('portfolios', $stored, 'public');

        $portfolio = Portfolio::create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_name' => $stored,
            'path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);

        return response()->json(['file' => $portfolio->toApi()], 201);
    }

    public function destroy(Request $request, Portfolio $portfolio): JsonResponse
    {
        abort_if($portfolio->user_id !== $request->user()->id, 403, 'Bukan milik Anda.');

        Storage::disk('public')->delete($portfolio->path);
        $portfolio->delete();

        return response()->json(['message' => 'File dihapus.']);
    }
}
