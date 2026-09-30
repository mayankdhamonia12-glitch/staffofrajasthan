<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;
use LogicException;

class DevelopmentRecruitmentDataSeeder extends Seeder
{
    /**
     * Populate realistic, explicitly fictional recruitment records for local UI development.
     * This seeder is intentionally not called by DatabaseSeeder.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            throw new LogicException('Development recruitment data may only be seeded in the local environment.');
        }

        $categories = collect([
            ['name' => 'IT & Software', 'slug' => 'it-software'],
            ['name' => 'Accounting & Finance', 'slug' => 'accounting-finance'],
            ['name' => 'Sales & Business Development', 'slug' => 'sales-business-development'],
            ['name' => 'Human Resources', 'slug' => 'human-resources'],
            ['name' => 'Design & Creative', 'slug' => 'design-creative'],
            ['name' => 'Customer Support', 'slug' => 'customer-support'],
        ])->mapWithKeys(fn (array $category): array => [
            $category['slug'] => JobCategory::query()->updateOrCreate(['slug' => $category['slug']], ['name' => $category['name'], 'is_active' => true]),
        ]);

        $industries = collect([
            'Technology' => 'technology',
            'Financial Services' => 'financial-services',
            'Business Services' => 'business-services',
            'Human Resources' => 'human-resources-industry',
            'Creative Services' => 'creative-services',
            'Customer Experience' => 'customer-experience',
        ])->mapWithKeys(fn (string $slug, string $name): array => [
            $name => Industry::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]),
        ]);

        $locations = collect(['Jaipur', 'Kota', 'Jodhpur', 'Udaipur', 'Ajmer'])->mapWithKeys(fn (string $name): array => [
            $name => Location::query()->updateOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'state' => 'Rajasthan', 'is_active' => true]),
        ]);

        $records = [
            ['company' => 'Amber Code Labs (Development)', 'role' => 'Laravel Developer', 'category' => 'it-software', 'industry' => 'Technology', 'location' => 'Jaipur'],
            ['company' => 'Blue Fort Accounts Demo (Development)', 'role' => 'Accountant', 'category' => 'accounting-finance', 'industry' => 'Financial Services', 'location' => 'Jodhpur'],
            ['company' => 'Aravalli Sales Studio (Development)', 'role' => 'Sales Executive', 'category' => 'sales-business-development', 'industry' => 'Business Services', 'location' => 'Udaipur'],
            ['company' => 'Pink City People Ops (Development)', 'role' => 'HR Executive', 'category' => 'human-resources', 'industry' => 'Human Resources', 'location' => 'Jaipur'],
            ['company' => 'Lakeview Creative Demo (Development)', 'role' => 'Graphic Designer', 'category' => 'design-creative', 'industry' => 'Creative Services', 'location' => 'Udaipur'],
            ['company' => 'Maru Connect Training (Development)', 'role' => 'Customer Support Executive', 'category' => 'customer-support', 'industry' => 'Customer Experience', 'location' => 'Kota'],
        ];

        foreach ($records as $index => $record) {
            $slug = str($record['company'])->slug();
            $email = 'development-employer-'.($index + 1).'@staffofrajasthan.test';
            $employer = User::query()->where('email', $email)->first()
                ?? User::factory()->employer()->create(['email' => $email, 'name' => $record['company']]);
            $company = $employer->companies()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $record['company'], 'industry_id' => $industries[$record['industry']]->id, 'location_id' => $locations[$record['location']]->id, 'is_visible' => true, 'description' => 'Fictional local development employer for Staff of Rajasthan UI testing.'],
            );
            $company->jobs()->updateOrCreate(
                ['slug' => str($record['role'])->slug().'-'.$slug],
                [
                    'title' => $record['role'],
                    'description' => 'Development sample listing for testing the public job portal. This role is fictional and is not a real vacancy.',
                    'responsibilities' => 'Use this local-only listing to review job details and public discovery flows.',
                    'requirements' => 'This sample listing is provided for development interface testing only.',
                    'employment_type' => 'full_time',
                    'experience_level' => 'Mid level',
                    'salary_min' => 300000,
                    'salary_max' => 600000,
                    'salary_period' => 'yearly',
                    'status' => 'published',
                    'is_featured' => $index < 3,
                    'published_at' => now()->subHours($index),
                    'job_category_id' => $categories[$record['category']]->id,
                    'industry_id' => $industries[$record['industry']]->id,
                    'location_id' => $locations[$record['location']]->id,
                ],
            );
        }
    }
}
