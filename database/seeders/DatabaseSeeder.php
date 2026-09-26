<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\JobListing;
use App\Models\Message;
use App\Models\Need;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed data sesuai mock di frontend (src/pages/JobMatchingPage.tsx).
     */
    public function run(): void
    {
        $hrd = User::updateOrCreate(
            ['email' => 'hrd@setarakerja.test'],
            [
                'name' => 'HR Manager',
                'password' => 'password123',
                'role' => 'hrd',
                'title' => 'Admin Officer',
                'company_name' => 'PT Unilever Indonesia',
            ]
        );

        $kandidat = User::updateOrCreate(
            ['email' => 'kandidat@setarakerja.test'],
            [
                'name' => 'Sari Rahayu',
                'password' => 'password123',
                'role' => 'kandidat',
                'title' => 'Software Engineer',
                'disability_type' => 'tunarungu',
            ]
        );

        $applicants = collect([
            ['email' => 'budi@setarakerja.test', 'name' => 'Budi Santoso', 'disability' => 'tunanetra'],
            ['email' => 'citra@setarakerja.test', 'name' => 'Citra Lestari', 'disability' => 'tunadaksa'],
            ['email' => 'dimas@setarakerja.test', 'name' => 'Dimas Pratama', 'disability' => 'autisme'],
        ])->map(fn (array $applicant) => User::updateOrCreate(
            ['email' => $applicant['email']],
            [
                'name' => $applicant['name'],
                'password' => 'password123',
                'role' => 'kandidat',
                'title' => 'Kandidat',
                'disability_type' => $applicant['disability'],
            ]
        ));

        $jobs = $this->jobs();

        JobListing::query()->delete();

        $coords = $this->coordinates();

        $seeded = collect($jobs)->map(function (array $job) use ($hrd, $coords) {
            $postedDays = $job['postedDays'];
            $categories = $job['category'];
            unset($job['postedDays'], $job['match'], $job['category']);

            $point = $coords[$job['company']] ?? null;

            return JobListing::create($job + [
                'user_id' => $hrd->id,
                'location_lat' => $point[0] ?? null,
                'location_lng' => $point[1] ?? null,
                'categories' => $categories,
                'posted_at' => now()->subDays($postedDays),
            ]);
        });

        Application::query()->delete();

        $statuses = ['applied', 'screening', 'interview_requested'];
        $skills = [
            [['skill' => 'React', 'score' => 92], ['skill' => 'TypeScript', 'score' => 88]],
            [['skill' => 'Excel', 'score' => 85], ['skill' => 'Akurasi Data', 'score' => 90]],
            [['skill' => 'Komunikasi', 'score' => 80], ['skill' => 'Empati', 'score' => 95]],
        ];

        // HRD demo (PT Unilever Indonesia) hanya boleh melihat lamaran ke
        // lowongan miliknya sendiri, jadi semua lamaran ditujukan ke sana.
        $hrdJob = $seeded->firstWhere('company', 'PT Unilever Indonesia') ?? $seeded->first();

        $applicants->values()->each(function (User $applicant, int $index) use ($hrdJob, $statuses, $skills) {
            Application::create([
                'user_id' => $applicant->id,
                'job_listing_id' => $hrdJob->id,
                'applicant_name' => $applicant->name,
                'applicant_email' => $applicant->email,
                'disability' => $applicant->disability_type,
                'accommodation' => 'remote',
                'status' => $statuses[$index] ?? 'applied',
                'anonymous_code' => 'K-'.random_int(1000, 9999),
                'ai_score' => random_int(75, 95),
                'skills' => collect($skills[$index] ?? $skills[0])->map(fn (array $skill) => $skill + [
                    'verifiedAt' => now()->subDays(random_int(1, 30))->toDateString(),
                ])->values()->all(),
                'is_revealed' => false,
                'applied_at' => now()->subDays($index + 1),
            ]);
        });

        // Satu lamaran ke perusahaan lain — tidak boleh tampil di dashboard HRD Unilever.
        $foreignJob = $seeded->first(fn (JobListing $job) => $job->id !== $hrdJob->id);
        $extraApplicant = $applicants->last();

        if ($foreignJob && $extraApplicant) {
            Application::create([
                'user_id' => $extraApplicant->id,
                'job_listing_id' => $foreignJob->id,
                'applicant_name' => $extraApplicant->name,
                'applicant_email' => $extraApplicant->email,
                'disability' => $extraApplicant->disability_type,
                'accommodation' => 'remote',
                'status' => 'applied',
                'anonymous_code' => 'K-'.random_int(1000, 9999),
                'ai_score' => random_int(75, 95),
                'skills' => collect($skills[0])->map(fn (array $skill) => $skill + [
                    'verifiedAt' => now()->subDays(random_int(1, 30))->toDateString(),
                ])->values()->all(),
                'is_revealed' => false,
                'applied_at' => now()->subDay(),
            ]);
        }

        Need::query()->delete();
        Need::create([
            'user_id' => $kandidat->id,
            'title' => 'Penerjemah Bahasa Isyarat saat interview',
            'description' => 'Membutuhkan penerjemah isyarat untuk sesi wawancara daring.',
            'category' => 'Komunikasi',
            'priority' => 'Tinggi',
            'status' => 'dibutuhkan',
        ]);

        // Skill Passport (tabel `skills`) — input skill manual milik kandidat demo.
        Skill::query()->where('user_id', $kandidat->id)->delete();
        collect([
            ['name' => 'Public Speaking', 'score' => 88, 'verified_at' => now()->subDays(2)],
            ['name' => 'Figma', 'score' => 83, 'verified_at' => now()->subDays(5)],
            ['name' => 'Node.js', 'score' => 74, 'verified_at' => null],
        ])->each(fn (array $skill) => Skill::query()->insert([
            'user_id' => $kandidat->id,
            'name' => $skill['name'],
            'score' => $skill['score'],
            'verified_at' => $skill['verified_at'],
            'created_at' => now()->subDays(7),
            'updated_at' => now(),
        ]));

        // Percakapan umum (tanpa tujuan lamaran/lowongan) — dibuat ulang tiap seed agar selalu ada.
        Message::query()->delete();
        Message::insert([
            [
                'user_id' => $kandidat->id,
                'thread' => 'feedback',
                'author' => 'kandidat',
                'author_name' => $kandidat->name,
                'body' => 'Halo, saya butuh penyesuaian jadwal kerja fleksibel.',
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'user_id' => $hrd->id,
                'thread' => 'feedback',
                'author' => 'hrd',
                'author_name' => $hrd->name,
                'body' => 'Baik, kami bisa atur jam kerja yang lebih fleksibel.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Satu pesan HRD ber-tujuan per lamaran agar fitur tujuan feedback langsung terisi.
        Application::query()->with('job')->get()->each(function (Application $application) use ($hrd) {
            Message::insert([
                'user_id' => $hrd->id,
                'application_id' => $application->id,
                'thread' => 'feedback',
                'author' => 'hrd',
                'author_name' => 'HR Manager',
                'body' => 'Terima kasih, lamaran Anda untuk "'.$application->job?->title.'" sedang kami proses.',
                'created_at' => now()->subHours(2),
                'updated_at' => now()->subHours(2),
            ]);
        });
    }

    /**
     * Titik lokasi kantor perusahaan pada lowongan (lat, lng).
     *
     * @return array<string, array{0: float, 1: float}>
     */
    private function coordinates(): array
    {
        return [
            'BRI Digital' => [-6.2088, 106.8456],
            'Tokopedia' => [-6.1885, 106.8290],
            'Shopee Indonesia' => [-6.2297, 106.8100],
            'PT Bank Mandiri' => [-6.2347, 106.7960],
            'Bukalapak' => [-6.9175, 107.6191],
            'Yayasan Pendidikan Anak Berkebutuhan Khusus' => [-7.7956, 110.3695],
            'Gojek' => [-6.1940, 106.8230],
            'Gramedia Digital' => [-6.1889, 106.7981],
            'PT Unilever Indonesia' => [-6.2000, 106.6500],
            'PT Sinarmas Logistic' => [-7.2575, 112.7521],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function jobs(): array
    {
        return [
            [
                'title' => 'Frontend Engineer', 'company' => 'BRI Digital',
                'company_size' => '500–1.000 karyawan', 'location' => 'Jakarta (Remote)',
                'type' => 'remote', 'salary' => 'Rp 8–14 jt/bln',
                'skills' => ['React', 'TypeScript', 'Figma'], 'match' => 94, 'postedDays' => 2,
                'accessible' => true, 'accommodations' => ['screen_reader', 'remote', 'flexible'],
                'category' => ['tunanetra', 'tunarungu'], 'job_coach' => 'Yolanda S.',
                'slots' => 3, 'verified' => true,
            ],
            [
                'title' => 'UI/UX Designer', 'company' => 'Tokopedia',
                'company_size' => '1.000–5.000 karyawan', 'location' => 'Jakarta (Hybrid)',
                'type' => 'hybrid', 'salary' => 'Rp 10–16 jt/bln',
                'skills' => ['Figma', 'Prototyping', 'User Research'], 'match' => 88, 'postedDays' => 5,
                'accessible' => true, 'accommodations' => ['captioning', 'sign_language', 'flexible'],
                'category' => ['tunarungu', 'tunawicara'], 'job_coach' => 'Bimo R.',
                'slots' => 2, 'verified' => true,
            ],
            [
                'title' => 'Data Entry Specialist', 'company' => 'Shopee Indonesia',
                'company_size' => '5.000+ karyawan', 'location' => 'Jakarta (Onsite)',
                'type' => 'onsite', 'salary' => 'Rp 5–8 jt/bln',
                'skills' => ['Excel', 'Akurasi Data', 'Disiplin'], 'match' => 82, 'postedDays' => 1,
                'accessible' => true, 'accommodations' => ['screen_reader', 'wheelchair', 'flexible'],
                'category' => ['tunadaksa', 'tunanetra'], 'job_coach' => 'Dewi P.',
                'slots' => 5, 'verified' => true,
            ],
            [
                'title' => 'Staf Administrasi Keuangan', 'company' => 'PT Bank Mandiri',
                'company_size' => '10.000+ karyawan', 'location' => 'Jakarta (Hybrid)',
                'type' => 'hybrid', 'salary' => 'Rp 6–10 jt/bln',
                'skills' => ['Akuntansi', 'Excel', 'Detail-oriented'], 'match' => 79, 'postedDays' => 3,
                'accessible' => true, 'accommodations' => ['captioning', 'wheelchair', 'assistive_tech'],
                'category' => ['tunadaksa', 'tunarungu'], 'job_coach' => 'Rian F.',
                'slots' => 4, 'verified' => true,
            ],
            [
                'title' => 'Customer Service Representative', 'company' => 'Bukalapak',
                'company_size' => '1.000–5.000 karyawan', 'location' => 'Bandung (Remote)',
                'type' => 'remote', 'salary' => 'Rp 4–7 jt/bln',
                'skills' => ['Komunikasi', 'Empati', 'Problem Solving'], 'match' => 75, 'postedDays' => 7,
                'accessible' => true, 'accommodations' => ['sign_language', 'remote', 'captioning'],
                'category' => ['tunarungu', 'tunawicara'], 'job_coach' => 'Yolanda S.',
                'slots' => 6, 'verified' => false,
            ],
            [
                'title' => 'Guru Pendamping Inklusi (GPK)', 'company' => 'Yayasan Pendidikan Anak Berkebutuhan Khusus',
                'company_size' => '50–200 karyawan', 'location' => 'Yogyakarta (Onsite)',
                'type' => 'onsite', 'salary' => 'Rp 4–6 jt/bln',
                'skills' => ['Pendidikan Inklusif', 'Sabar', 'Kreatif'], 'match' => 91, 'postedDays' => 2,
                'accessible' => true, 'accommodations' => ['assistive_tech', 'flexible', 'sign_language'],
                'category' => ['autisme', 'tunadaksa', 'tunarungu', 'tunanetra'], 'job_coach' => 'Dewi P.',
                'slots' => 3, 'verified' => true,
            ],
            [
                'title' => 'Quality Assurance Tester', 'company' => 'Gojek',
                'company_size' => '5.000+ karyawan', 'location' => 'Jakarta (Hybrid)',
                'type' => 'hybrid', 'salary' => 'Rp 7–12 jt/bln',
                'skills' => ['Testing', 'Jira', 'Attention to Detail'], 'match' => 85, 'postedDays' => 4,
                'accessible' => true, 'accommodations' => ['screen_reader', 'wheelchair', 'flexible'],
                'category' => ['tunadaksa', 'tunanetra'], 'job_coach' => 'Bimo R.',
                'slots' => 2, 'verified' => true,
            ],
            [
                'title' => 'Content Writer & Translator', 'company' => 'Gramedia Digital',
                'company_size' => '200–500 karyawan', 'location' => 'Jakarta (Remote)',
                'type' => 'remote', 'salary' => 'Rp 5–9 jt/bln',
                'skills' => ['Menulis', 'Terjemahan', 'SEO'], 'match' => 77, 'postedDays' => 6,
                'accessible' => true, 'accommodations' => ['screen_reader', 'remote', 'captioning'],
                'category' => ['tunanetra', 'autisme'], 'job_coach' => 'Dewi P.',
                'slots' => 3, 'verified' => true,
            ],
            [
                'title' => 'Operator Produksi', 'company' => 'PT Unilever Indonesia',
                'company_size' => '5.000+ karyawan', 'location' => 'Tangerang (Onsite)',
                'type' => 'onsite', 'salary' => 'Rp 4–6 jt/bln',
                'skills' => ['Disiplin', 'Ketelitian', 'Kerja Tim'], 'match' => 68, 'postedDays' => 2,
                'accessible' => true, 'accommodations' => ['wheelchair', 'flexible', 'sign_language'],
                'category' => ['tunadaksa', 'tunarungu'], 'job_coach' => 'Rian F.',
                'slots' => 8, 'verified' => false,
            ],
            [
                'title' => 'Admin Logistik Gudang', 'company' => 'PT Sinarmas Logistic',
                'company_size' => '1.000–5.000 karyawan', 'location' => 'Surabaya (Hybrid)',
                'type' => 'hybrid', 'salary' => 'Rp 4,5–6,5 jt/bln',
                'skills' => ['Inventori', 'Excel', 'Organisasi'], 'match' => 71, 'postedDays' => 5,
                'accessible' => true, 'accommodations' => ['flexible', 'wheelchair', 'assistive_tech'],
                'category' => ['tunadaksa', 'autisme'], 'job_coach' => null,
                'slots' => 4, 'verified' => true,
            ],
        ];
    }
}
