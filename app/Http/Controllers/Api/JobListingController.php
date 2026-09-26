<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobListingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JobListing::query()->orderByDesc('posted_at')->orderByDesc('id');

        if ($request->filled('q')) {
            $q = $request->string('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('title', 'like', "%{$q}%")
                    ->orWhere('company', 'like', "%{$q}%")
                    ->orWhere('location', 'like', "%{$q}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('category')) {
            $query->where('categories', 'like', '%'.$request->string('category').'%');
        }

        if ($request->boolean('accessible')) {
            $query->where('accessible', true);
        }

        $jobs = $query->get()->map(fn (JobListing $job) => $job->toApi());

        return response()->json(['jobs' => $jobs]);
    }

    public function show(JobListing $job): JsonResponse
    {
        return response()->json(['job' => $job->toApi()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'company' => ['required', 'string', 'max:255'],
            'companySize' => ['nullable', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'locationLat' => ['nullable', 'numeric', 'between:-90,90', 'required_with:locationLng'],
            'locationLng' => ['nullable', 'numeric', 'between:-180,180', 'required_with:locationLat'],
            'type' => ['required', 'in:remote,hybrid,onsite'],
            'salary' => ['nullable', 'string', 'max:100'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
            'accommodations' => ['nullable', 'array'],
            'category' => ['nullable', 'array'],
            'jobCoach' => ['required', 'string', 'max:255'],
            'slots' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'verified' => ['boolean'],
            'accessible' => ['boolean'],
            'description' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        if (! in_array($user->role, ['hrd', 'admin'], true)) {
            return response()->json(['message' => 'Hanya HRD yang dapat memasang lowongan.'], 403);
        }

        // HRD hanya boleh memasang lowongan atas nama perusahaannya sendiri.
        $company = $user->role === 'hrd' && trim((string) $user->company_name) !== ''
            ? $user->company_name
            : $data['company'];

        $job = JobListing::create([
            'user_id' => $user->id,
            'title' => $data['title'],
            'company' => $company,
            'company_size' => $data['companySize'] ?? null,
            'location' => $data['location'],
            'location_lat' => isset($data['locationLat']) ? (float) $data['locationLat'] : null,
            'location_lng' => isset($data['locationLng']) ? (float) $data['locationLng'] : null,
            'type' => $data['type'],
            'salary' => $data['salary'] ?? null,
            'skills' => $data['skills'] ?? [],
            'accessible' => $data['accessible'] ?? true,
            'accommodations' => $data['accommodations'] ?? [],
            'categories' => $data['category'] ?? [],
            'job_coach' => $data['jobCoach'] ?? null,
            'slots' => $data['slots'] ?? 1,
            'verified' => $data['verified'] ?? false,
            'description' => $data['description'] ?? null,
            'posted_at' => now(),
        ]);

        return response()->json(['job' => $job->toApi()], 201);
    }
}
