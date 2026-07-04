<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    private array $users = [];
    private array $companies = [];
    private array $categories = [];
    private ?int $governorateId = null;
    private ?int $cityId = null;

    public function run(): void
    {
        $this->loadLookups();
        $this->seedUsers();
        $this->seedServices();
        $this->seedProjects();
        $this->seedJobs();
        $this->seedRequestsAndApplications();
        $this->seedMessages();
        $this->seedNotifications();
    }

    private function loadLookups(): void
    {
        $this->categories = DB::table('categories')
            ->pluck('id', 'name')
            ->all();

        foreach (['Web Development', 'Mobile Development', 'UI/UX Design', 'Testing & QA', 'Artificial Intelligence'] as $name) {
            if (! isset($this->categories[$name])) {
                $this->categories[$name] = DB::table('categories')->insertGetId([
                    'name' => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->governorateId = DB::table('governorates')->value('id');
        $this->cityId = DB::table('cities')->value('id');
    }

    private function seedUsers(): void
    {
        $people = [
            ['key' => 'ahmad', 'name' => 'Ahmad Khaled', 'email' => 'ahmad.demo@gmail.com', 'title' => 'Laravel Backend Developer'],
            ['key' => 'sara', 'name' => 'Sara Mansour', 'email' => 'sara.demo@gmail.com', 'title' => 'UI UX Designer'],
            ['key' => 'omar', 'name' => 'Omar Nasser', 'email' => 'omar.demo@gmail.com', 'title' => 'Flutter Developer'],
            ['key' => 'lina', 'name' => 'Lina Haddad', 'email' => 'lina.demo@gmail.com', 'title' => 'QA Engineer'],
            ['key' => 'yara', 'name' => 'Yara Saleh', 'email' => 'yara.demo@gmail.com', 'title' => 'AI Engineer'],
            ['key' => 'mohammad', 'name' => 'Mohammad Darwish', 'email' => 'mohammad.demo@gmail.com', 'title' => 'DevOps Engineer'],
            ['key' => 'nour', 'name' => 'Nour Barakat', 'email' => 'nour.demo@gmail.com', 'title' => 'Product Manager'],
        ];

        foreach ($people as $index => $person) {
            $userId = $this->upsertId('users', ['email' => $person['email']], [
                'name' => $person['name'],
                'email' => $person['email'],
                'email_verified_at' => now(),
                'role' => 'personal',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->users[$person['key']] = $userId;

            DB::table('profiles')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'description' => $person['title'],
                    'address' => 'Demo address ' . ($index + 1),
                    'bio' => 'Experienced ' . $person['title'] . ' available for WorkBridge demo projects.',
                    'phone' => '+96390000000' . $index,
                    'rating_avg' => $index < 5 ? 4.5 : 0,
                    'governorate_id' => $this->governorateId,
                    'city_id' => $this->cityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $this->seedUserDefaults($userId, 250 + ($index * 75));
        }

        $companyUsers = [
            ['key' => 'techvision', 'name' => 'TechVision User', 'email' => 'techvision.demo@gmail.com', 'company' => 'TechVision'],
            ['key' => 'smartsolutions', 'name' => 'SmartSolutions User', 'email' => 'smartsolutions.demo@gmail.com', 'company' => 'Smart Solutions'],
            ['key' => 'creativehub', 'name' => 'CreativeHub User', 'email' => 'creativehub.demo@gmail.com', 'company' => 'Creative Hub'],
        ];

        foreach ($companyUsers as $index => $companyUser) {
            $userId = $this->upsertId('users', ['email' => $companyUser['email']], [
                'name' => $companyUser['name'],
                'email' => $companyUser['email'],
                'email_verified_at' => now(),
                'role' => 'company',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->users[$companyUser['key']] = $userId;

            $companyId = $this->upsertId('companies', ['user_id' => $userId], [
                'user_id' => $userId,
                'company_name' => $companyUser['company'],
                'logo' => null,
                'website' => 'https://example.com/' . strtolower(str_replace(' ', '-', $companyUser['company'])),
                'location' => 'Damascus',
                'phone' => '+96391100000' . $index,
                'description' => $companyUser['company'] . ' is a demo company hiring and buying services on WorkBridge.',
                'is_verified' => true,
                'governorate_id' => $this->governorateId,
                'city_id' => $this->cityId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->companies[$companyUser['key']] = $companyId;
            $this->seedUserDefaults($userId, 1500 + ($index * 500));
        }
    }

    private function seedServices(): void
    {
        $services = [
            ['owner' => 'ahmad', 'category' => 'Web Development', 'title' => 'Build a Laravel REST API', 'price' => 350, 'days' => 7],
            ['owner' => 'sara', 'category' => 'UI/UX Design', 'title' => 'Design a mobile app UI kit', 'price' => 280, 'days' => 5],
            ['owner' => 'omar', 'category' => 'Mobile Development', 'title' => 'Create a Flutter MVP', 'price' => 600, 'days' => 14],
            ['owner' => 'lina', 'category' => 'Testing & QA', 'title' => 'Manual QA test cycle', 'price' => 180, 'days' => 4],
            ['owner' => 'yara', 'category' => 'Artificial Intelligence', 'title' => 'Prototype an AI recommendation model', 'price' => 750, 'days' => 12],
        ];

        foreach ($services as $service) {
            $this->upsertId('services', ['user_id' => $this->users[$service['owner']], 'title' => $service['title']], [
                'user_id' => $this->users[$service['owner']],
                'category_id' => $this->categories[$service['category']],
                'title' => $service['title'],
                'description' => 'Demo service: ' . $service['title'],
                'price' => $service['price'],
                'delivery_days' => $service['days'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedProjects(): void
    {
        $projects = [
            ['owner' => 'techvision', 'category' => 'Web Development', 'title' => 'E-commerce dashboard backend', 'budget' => 900, 'days' => 18],
            ['owner' => 'creativehub', 'category' => 'UI/UX Design', 'title' => 'Brand landing page redesign', 'budget' => 450, 'days' => 10],
            ['owner' => 'smartsolutions', 'category' => 'Mobile Development', 'title' => 'Employee attendance mobile app', 'budget' => 1200, 'days' => 25],
            ['owner' => 'nour', 'category' => 'Artificial Intelligence', 'title' => 'Smart product search prototype', 'budget' => 800, 'days' => 15],
            ['owner' => 'mohammad', 'category' => 'Testing & QA', 'title' => 'Regression testing for SaaS platform', 'budget' => 300, 'days' => 6],
        ];

        foreach ($projects as $project) {
            $this->upsertId('user_projects', ['user_id' => $this->users[$project['owner']], 'title' => $project['title']], [
                'user_id' => $this->users[$project['owner']],
                'category_id' => $this->categories[$project['category']],
                'title' => $project['title'],
                'description' => 'Demo project brief for ' . $project['title'],
                'budget' => $project['budget'],
                'duration_days' => $project['days'],
                'status' => 'active',
                'governorate_id' => $this->governorateId,
                'city_id' => $this->cityId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedJobs(): void
    {
        $jobs = [
            ['company' => 'techvision', 'title' => 'Backend Laravel Developer', 'salary' => 1200, 'location_type' => 'hybrid'],
            ['company' => 'smartsolutions', 'title' => 'Flutter Developer', 'salary' => 1000, 'location_type' => 'remote'],
            ['company' => 'creativehub', 'title' => 'Product UI Designer', 'salary' => 850, 'location_type' => 'on_site'],
            ['company' => 'techvision', 'title' => 'QA Automation Engineer', 'salary' => 900, 'location_type' => 'remote'],
            ['company' => 'smartsolutions', 'title' => 'Junior DevOps Engineer', 'salary' => 750, 'location_type' => 'hybrid'],
        ];

        foreach ($jobs as $job) {
            $this->upsertId('job_posts', ['company_id' => $this->companies[$job['company']], 'title' => $job['title']], [
                'company_id' => $this->companies[$job['company']],
                'title' => $job['title'],
                'description' => 'Demo job opening for ' . $job['title'],
                'location_type' => $job['location_type'],
                'city_id' => $this->cityId,
                'salary' => $job['salary'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedRequestsAndApplications(): void
    {
        $project = DB::table('user_projects')->where('title', 'E-commerce dashboard backend')->first();
        $applicationId = $this->upsertId('applications', ['user_project_id' => $project->id, 'user_id' => $this->users['ahmad']], [
            'user_project_id' => $project->id,
            'user_id' => $this->users['ahmad'],
            'price' => 850,
            'duration_days' => 16,
            'description' => 'I can build a clean Laravel API with admin endpoints and documentation.',
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $contractId = $this->upsertId('contracts', ['application_id' => $applicationId], [
            'client_id' => $this->users['techvision'],
            'freelancer_id' => $this->users['ahmad'],
            'user_project_id' => $project->id,
            'service_request_id' => null,
            'job_post_id' => null,
            'application_id' => $applicationId,
            'amount' => 850,
            'commission_amount' => 85,
            'freelancer_amount' => 765,
            'status' => 'completed',
            'funded_at' => Carbon::now()->subDays(12),
            'completed_at' => Carbon::now()->subDays(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('reviews')->updateOrInsert(
            ['contract_id' => $contractId, 'reviewer_id' => $this->users['techvision'], 'reviewed_user_id' => $this->users['ahmad']],
            ['rating' => 5, 'comment' => 'Great API work and clear communication.', 'created_at' => now(), 'updated_at' => now()]
        );

        $service = DB::table('services')->where('title', 'Design a mobile app UI kit')->first();
        $serviceRequestId = $this->upsertId('service_requests', ['service_id' => $service->id, 'client_id' => $this->users['creativehub'], 'title' => 'UI kit for marketplace app'], [
            'service_id' => $service->id,
            'client_id' => $this->users['creativehub'],
            'title' => 'UI kit for marketplace app',
            'description' => 'Need polished screens for onboarding, catalog, checkout, and profile.',
            'references' => 'Figma references will be shared after kickoff.',
            'delivery_days' => 6,
            'status' => 'accepted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->upsertId('contracts', ['service_request_id' => $serviceRequestId], [
            'client_id' => $this->users['creativehub'],
            'freelancer_id' => $this->users['sara'],
            'user_project_id' => null,
            'service_request_id' => $serviceRequestId,
            'job_post_id' => null,
            'application_id' => null,
            'amount' => 280,
            'commission_amount' => 28,
            'freelancer_amount' => 252,
            'status' => 'funded',
            'funded_at' => Carbon::now()->subDays(3),
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $job = DB::table('job_posts')->where('title', 'Flutter Developer')->first();
        DB::table('job_apply')->updateOrInsert(
            ['job_id' => $job->id, 'user_id' => $this->users['omar']],
            ['status' => 'accepted', 'created_at' => now(), 'updated_at' => now()]
        );

        $this->upsertId('contracts', ['job_post_id' => $job->id, 'freelancer_id' => $this->users['omar']], [
            'client_id' => $this->users['smartsolutions'],
            'freelancer_id' => $this->users['omar'],
            'user_project_id' => null,
            'service_request_id' => null,
            'job_post_id' => $job->id,
            'application_id' => null,
            'amount' => 1000,
            'commission_amount' => 100,
            'freelancer_amount' => 900,
            'status' => 'active',
            'funded_at' => Carbon::now()->subDay(),
            'completed_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedMessages(): void
    {
        $conversationId = $this->upsertId('conversations', ['user1_id' => $this->users['techvision'], 'user2_id' => $this->users['ahmad']], [
            'user1_id' => $this->users['techvision'],
            'user2_id' => $this->users['ahmad'],
            'last_message_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('messages')->updateOrInsert(
            ['conversation_id' => $conversationId, 'sender_id' => $this->users['techvision'], 'content' => 'Can you share your API delivery plan?'],
            ['type' => 'text', 'edited_at' => null, 'read_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('messages')->updateOrInsert(
            ['conversation_id' => $conversationId, 'sender_id' => $this->users['ahmad'], 'content' => 'Sure, I will split it into auth, dashboard, and reporting milestones.'],
            ['type' => 'text', 'edited_at' => null, 'read_at' => null, 'created_at' => now(), 'updated_at' => now()]
        );
    }

    private function seedNotifications(): void
    {
        $notifications = [
            ['user' => 'ahmad', 'type' => 'application_accepted', 'title' => 'Application accepted', 'message' => 'TechVision accepted your offer.'],
            ['user' => 'sara', 'type' => 'service_request', 'title' => 'New service request', 'message' => 'Creative Hub requested your UI kit service.'],
            ['user' => 'omar', 'type' => 'job_application_accepted', 'title' => 'Job application accepted', 'message' => 'Smart Solutions accepted your job application.'],
            ['user' => 'techvision', 'type' => 'project_update', 'title' => 'Project contract completed', 'message' => 'The backend contract is marked completed.'],
        ];

        foreach ($notifications as $notification) {
            DB::table('user_notifications')->updateOrInsert(
                [
                    'user_id' => $this->users[$notification['user']],
                    'type' => $notification['type'],
                    'title' => $notification['title'],
                ],
                [
                    'message' => $notification['message'],
                    'read_at' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function seedUserDefaults(int $userId, float $balance): void
    {
        DB::table('wallets')->updateOrInsert(
            ['user_id' => $userId],
            ['type' => 'user', 'balance' => $balance, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('user_settings')->updateOrInsert(
            ['user_id' => $userId],
            [
                'profile_visible' => true,
                'contact_permission' => 'all',
                'message_notifications' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function upsertId(string $table, array $keys, array $values): int
    {
        DB::table($table)->updateOrInsert($keys, $values);

        return (int) DB::table($table)
            ->where($keys)
            ->value('id');
    }
}
