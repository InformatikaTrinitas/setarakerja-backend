<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use App\Models\Portfolio;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApplicationController extends Controller
{
    /**
     * Blind apply dari JobMatchingPage (ApplyModal).
     */
    public function store(Request $request, JobListing $job): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'disability' => ['required', 'string', 'max:50'],
            'accommodation' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();

        $existing = Application::where('user_id', $user->id)
            ->where('job_listing_id', $job->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Anda sudah melamar pekerjaan ini.',
                'application' => $existing->toApi(),
            ], 422);
        }

        $skills = collect($job->skills ?? [])->map(fn ($skill) => [
            'skill' => $skill,
            'score' => random_int(65, 98),
            'verifiedAt' => now()->subDays(random_int(1, 30))->toDateString(),
        ])->values()->all();

        $application = Application::create([
            'user_id' => $user->id,
            'job_listing_id' => $job->id,
            'applicant_name' => $data['name'],
            'applicant_email' => $data['email'],
            'disability' => $data['disability'],
            'accommodation' => $data['accommodation'] ?? null,
            'status' => 'applied',
            'anonymous_code' => 'K-'.random_int(1000, 9999),
            'ai_score' => random_int(75, 96),
            'skills' => $skills,
            'applied_at' => now(),
        ]);

        return response()->json(['application' => $application->toApi()], 201);
    }

    /**
     * Daftar lamaran milik kandidat yang sedang login.
     */
    public function mine(Request $request): JsonResponse
    {
        $applications = Application::with('job')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('applied_at')
            ->get()
            ->map(fn (Application $application) => $application->toApi());

        return response()->json(['applications' => $applications]);
    }

    /**
     * Daftar kandidat untuk HRD (masked).
     *
     * HRD hanya melihat lamaran untuk lowongan di perusahaannya sendiri.
     */
    public function index(Request $request): JsonResponse
    {
        $company = $this->hrdCompany($request->user());

        if ($company === null) {
            return response()->json([
                'candidates' => [],
                'message' => 'Akun HRD Anda belum memiliki nama perusahaan.',
            ]);
        }

        $query = Application::with('job')
            ->whereHas('job', fn ($builder) => $builder->whereRaw('LOWER(company) = ?', [$company]))
            ->orderByDesc('applied_at');

        if ($request->filled('job_id')) {
            $query->where('job_listing_id', $request->input('job_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $candidates = $query->get()->map(fn (Application $application) => $application->toCandidateApi());

        return response()->json(['candidates' => $candidates, 'company' => $request->user()->company_name]);
    }

    public function show(Request $request, Application $application): JsonResponse
    {
        if ($denied = $this->guardCompany($request, $application)) {
            return $denied;
        }

        return response()->json([
            'application' => $application->toApi(),
            'candidate' => $application->toCandidateApi(),
            'skills' => Skill::where('user_id', $application->user_id)
                ->orderByDesc('score')
                ->get()
                ->map(fn (Skill $skill) => $skill->toApi())
                ->values(),
            'portfolios' => Portfolio::where('user_id', $application->user_id)
                ->orderByDesc('id')
                ->get()
                ->map(fn (Portfolio $file) => $file->toApi())
                ->values(),
        ]);
    }

    public function updateStatus(Request $request, Application $application): JsonResponse
    {
        if ($denied = $this->guardCompany($request, $application)) {
            return $denied;
        }

        $data = $request->validate([
            'status' => ['required', 'in:applied,screening,interview_requested,interview_scheduled,hired,rejected'],
        ]);

        $application->update(['status' => $data['status']]);

        return response()->json(['candidate' => $application->toCandidateApi()]);
    }

    /**
     * Reveal identitas kandidat oleh HRD.
     */
    public function reveal(Request $request, Application $application): JsonResponse
    {
        if ($denied = $this->guardCompany($request, $application)) {
            return $denied;
        }

        $application->update([
            'is_revealed' => true,
            'revealed_name' => $application->applicant_name,
            'revealed_disability' => Str::headline($application->disability ?? 'lainnya'),
            'revealed_accommodations' => array_values(array_filter([
                $application->accommodation ? Str::headline(str_replace('_', ' ', $application->accommodation)) : null,
            ])),
        ]);

        return response()->json(['candidate' => $application->fresh()->toCandidateApi()]);
    }

    /**
     * Nama perusahaan (lowercase) milik user HRD; null bila bukan HRD / belum diisi.
     */
    private function hrdCompany(?object $user): ?string
    {
        if (! $user || $user->role !== 'hrd') {
            return null;
        }

        $company = mb_strtolower(trim((string) $user->company_name));

        return $company === '' ? null : $company;
    }

    /**
     * Pastikan HRD hanya boleh menyentuh lamaran ke perusahaannya sendiri.
     */
    private function guardCompany(Request $request, Application $application): ?JsonResponse
    {
        $company = $this->hrdCompany($request->user());

        if ($company === null) {
            return response()->json(['message' => 'Hanya HRD dengan perusahaan terdaftar yang dapat melakukan aksi ini.'], 403);
        }

        $jobCompany = mb_strtolower(trim((string) ($application->job?->company ?? '')));

        if ($jobCompany !== $company) {
            return response()->json(['message' => 'Lamaran ini bukan untuk perusahaan Anda.'], 403);
        }

        return null;
    }
}
