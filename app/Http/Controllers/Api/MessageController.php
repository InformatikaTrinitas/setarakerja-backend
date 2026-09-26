<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Message::query()->orderBy('id');

        if ($request->filled('thread')) {
            $query->where('thread', $request->input('thread'));
        }

        // Filter tujuan pesan (kandidat tertentu / lowongan tertentu / umum).
        if ($request->filled('applicationId')) {
            $application = Application::with('job')->find($request->input('applicationId'));

            if (! $application) {
                return response()->json(['message' => 'Lamaran tidak ditemukan.'], 404);
            }

            if ($denied = $this->guardTarget($request, $application, null)) {
                return $denied;
            }

            $query->where('application_id', $application->id);
        } elseif ($request->filled('jobId')) {
            $job = JobListing::find($request->input('jobId'));

            if (! $job) {
                return response()->json(['message' => 'Lowongan tidak ditemukan.'], 404);
            }

            if ($denied = $this->guardTarget($request, null, $job)) {
                return $denied;
            }

            $query->where('job_id', $job->id);
        } elseif ($request->filled('company')) {
            // Thread per perusahaan: semua pesan yang terikat lamaran/lowongan
            // di perusahaan tersebut (dipakai halaman Feedback kandidat).
            $company = mb_strtolower(trim((string) $request->input('company')));

            if ($company === '') {
                return response()->json(['message' => 'Nama perusahaan wajib diisi.'], 422);
            }

            if ($denied = $this->guardCompany($request, $company)) {
                return $denied;
            }

            $query->where(function ($builder) use ($company) {
                $builder->whereHas('application.job', fn ($q) => $q->whereRaw('LOWER(company) = ?', [$company]))
                    ->orWhereHas('job', fn ($q) => $q->whereRaw('LOWER(company) = ?', [$company]));
            });
        } elseif ($request->boolean('general')) {
            $query->whereNull('application_id')->whereNull('job_id');
        }

        $messages = $query->get()->map(fn (Message $message) => $message->toApi());

        return response()->json(['messages' => $messages]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'text' => ['required', 'string', 'max:5000'],
            'thread' => ['sometimes', 'in:feedback,interview'],
            'author' => ['sometimes', 'in:kandidat,hrd'],
            'applicationId' => ['nullable', 'integer', 'exists:applications,id'],
            'jobId' => ['nullable', 'integer', 'exists:job_listings,id'],
            'company' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();

        $application = isset($data['applicationId']) ? Application::with('job')->find($data['applicationId']) : null;
        $job = isset($data['jobId']) ? JobListing::find($data['jobId']) : null;

        if ($denied = $this->guardTarget($request, $application, $job)) {
            return $denied;
        }

        // Thread per perusahaan (kandidat): sematkan ke lamaran miliknya di
        // perusahaan tersebut supaya pesan punya tujuan yang jelas.
        if (! $application && ! $job && isset($data['company']) && $data['company'] !== '') {
            $company = mb_strtolower(trim($data['company']));

            if ($denied = $this->guardCompany($request, $company)) {
                return $denied;
            }

            $application = $this->applicationForCompany($user, $company);

            if (! $application) {
                return response()->json(['message' => 'Anda belum melamar ke perusahaan ini.'], 403);
            }
        }

        $message = Message::create([
            'user_id' => $user?->id,
            'application_id' => $application?->id,
            'job_id' => $job?->id,
            'thread' => $data['thread'] ?? 'feedback',
            'author' => $data['author'] ?? ($user ? $user->role : 'kandidat'),
            'author_name' => $user?->name,
            'body' => $data['text'],
        ]);

        return response()->json(['message' => $message->toApi()], 201);
    }

    /**
     * Pastikan pesan hanya ditujukan ke lamaran milik pengirim atau lowongan
     * milik perusahaan HRD yang bersangkutan.
     */
    private function guardTarget(Request $request, ?Application $application, ?JobListing $job): ?JsonResponse
    {
        $user = $request->user();

        if (! $application && ! $job) {
            return null;
        }

        if (! $user) {
            return response()->json(['message' => 'Silakan masuk terlebih dahulu.'], 403);
        }

        // Pesan per kandidat: boleh untuk lamaran sendiri (kandidat)
        // atau lamaran ke perusahaan sendiri (HRD).
        if ($application) {
            if ($application->user_id === $user->id) {
                return null;
            }

            if ($user->role === 'hrd' && $this->isOwnCompany($user, (string) ($application->job?->company))) {
                return null;
            }

            return response()->json(['message' => 'Lamaran ini bukan untuk perusahaan Anda.'], 403);
        }

        // Pesan per lowongan: hanya HRD perusahaan pemilik lowongan.
        if ($user->role === 'hrd' && $this->isOwnCompany($user, (string) $job->company)) {
            return null;
        }

        return response()->json(['message' => 'Lowongan ini bukan milik perusahaan Anda.'], 403);
    }

    /**
     * Otorisasi thread per perusahaan:
     * - Kandidat: hanya boleh jika ia pernah melamar ke perusahaan tsb.
     * - HRD: hanya perusahaan miliknya sendiri.
     */
    private function guardCompany(Request $request, string $company): ?JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Silakan masuk terlebih dahulu.'], 403);
        }

        if ($user->role === 'hrd') {
            $own = mb_strtolower(trim((string) $user->company_name));

            if ($own === '' || $own !== $company) {
                return response()->json(['message' => 'Perusahaan ini bukan milik Anda.'], 403);
            }

            return null;
        }

        if (! $this->applicationForCompany($user, $company)) {
            return response()->json(['message' => 'Anda belum melamar ke perusahaan ini.'], 403);
        }

        return null;
    }

    /**
     * Lamaran milik user ke perusahaan tertentu (paling baru dipakai).
     */
    private function applicationForCompany(?object $user, string $company): ?Application
    {
        if (! $user) {
            return null;
        }

        return Application::with('job')
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->first(fn (Application $application) => mb_strtolower(trim((string) ($application->job?->company ?? ''))) === $company);
    }

    private function isOwnCompany(object $user, string $jobCompany): bool
    {
        $company = mb_strtolower(trim((string) $user->company_name));

        return $company !== '' && mb_strtolower(trim($jobCompany)) === $company;
    }
}
