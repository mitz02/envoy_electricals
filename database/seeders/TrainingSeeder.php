<?php

namespace Database\Seeders;

use App\Models\Training;
use App\Models\TrainingLesson;
use App\Models\TrainingWeek;
use App\Models\User;
use Illuminate\Database\Seeder;

class TrainingSeeder extends Seeder
{
    public static function buildCurriculum(Training $training): void
    {
        if ($training->weeks()->count() > 0) {
            return;
        }

        $topics = collect(preg_split('/\R/', $training->curriculum))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values();

        $weekCount = max(1, $training->duration_weeks ?: 1);
        $topicCount = $topics->count();

        for ($weekNumber = 1; $weekNumber <= $weekCount; $weekNumber++) {
            $start = (int) floor(($weekNumber - 1) * $topicCount / $weekCount);
            $end = (int) floor($weekNumber * $topicCount / $weekCount);
            $chunk = $topics->slice($start, $end - $start);

            if ($chunk->isEmpty()) {
                continue;
            }

            $week = TrainingWeek::create([
                'training_id' => $training->id,
                'week_number' => $weekNumber,
                'title' => "Week {$weekNumber}: {$chunk->first()}",
                'summary' => $chunk->implode(', '),
            ]);

            $position = 1;
            foreach ($chunk as $topic) {
                TrainingLesson::create([
                    'training_week_id' => $week->id,
                    'title' => $topic,
                    'description' => "Practical, hands-on session covering {$topic}.",
                    'objectives' => "By the end of this lesson, you will be able to confidently apply key concepts of {$topic}.",
                    'duration_minutes' => 60,
                    'position' => $position++,
                ]);
            }
        }
    }

    public function run(): void
    {
        if (Training::count() > 0) {
            return;
        }

        $admin = User::where('email', 'owner@envoyelectric.com')->first();

        $year = now()->format('Y');
        $baseCount = Training::count();

        $trainings = [
            [
                'ref_id' => "TRG-{$year}-".str_pad($baseCount + 1, 6, '0', STR_PAD_LEFT),
                'title' => 'Solar PV Installation Fundamentals',
                'description' => 'Master the basics of solar photovoltaic system installation. This hands-on course covers site assessment, panel mounting, wiring, inverter connection, and system commissioning. Perfect for beginners entering the solar industry.',
                'curriculum' => "Introduction to Solar Energy & PV Technology\nSafety Procedures & PPE Requirements\nSite Survey & Energy Audit Techniques\nSolar Panel Types, Specifications & Selection\nMounting Structures: Roof & Ground Mount Systems\nDC Wiring, Connectors & Cable Management\nInverter Installation & Configuration\nBattery Bank Sizing & Installation\nSystem Testing, Commissioning & Handover\nMaintenance Basics & Troubleshooting",
                'level' => Training::LEVEL_BEGINNER,
                'duration_weeks' => 4,
                'price' => 150000,
                'capacity' => 20,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'ref_id' => "TRG-{$year}-".str_pad($baseCount + 2, 6, '0', STR_PAD_LEFT),
                'title' => 'Advanced Solar System Design & Engineering',
                'description' => 'Deep dive into system design, load analysis, and performance optimization. Learn to design residential, commercial, and industrial solar systems with advanced modeling tools and techniques.',
                'curriculum' => "Advanced Load Analysis & Energy Profiling\nPV System Sizing Methodologies\nShade Analysis & Mitigation Strategies\nBattery Storage System Design (Li-ion & Lead-Acid)\nGrid-Tied vs Off-Grid vs Hybrid System Design\nPerformance Modeling with PVsyst & SAM\nFinancial Modeling & ROI Calculations\nNigerian Grid Code & Regulatory Compliance\nLarge-Scale Commercial System Design\nProject Documentation & Permitting",
                'level' => Training::LEVEL_ADVANCED,
                'duration_weeks' => 6,
                'price' => 250000,
                'capacity' => 15,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'ref_id' => "TRG-{$year}-".str_pad($baseCount + 3, 6, '0', STR_PAD_LEFT),
                'title' => 'Inverter & Battery System Installation',
                'description' => 'Specialized training on inverter installation, configuration, and battery bank integration. Covers hybrid inverters, off-grid systems, and battery management systems.',
                'curriculum' => "Inverter Types: String, Micro, Hybrid, Off-Grid\nInverter Specifications & Selection Criteria\nAC/DC Wiring Standards & Protection Devices\nBattery Technologies: Lead-Acid, LiFePO4, Lithium-Ion\nBattery Bank Sizing, Configuration & Wiring\nBattery Management System (BMS) Setup\nInverter Programming & Parameter Configuration\nGrid Interaction & Anti-Islanding Protection\nSystem Monitoring & Remote Management\nFault Diagnosis & Repair Techniques",
                'level' => Training::LEVEL_INTERMEDIATE,
                'duration_weeks' => 3,
                'price' => 180000,
                'capacity' => 18,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'ref_id' => "TRG-{$year}-".str_pad($baseCount + 4, 6, '0', STR_PAD_LEFT),
                'title' => 'Electrical Wiring & Safety Certification',
                'description' => 'Comprehensive electrical wiring course focused on solar installations. Covers Nigerian Electrical Code, conduit installation, earthing, protection devices, and inspection procedures.',
                'curriculum' => "Nigerian Electrical Code (NEC) Overview\nElectrical Safety & Risk Assessment\nConduit, Trunking & Cable Tray Installation\nCable Selection, Sizing & Voltage Drop Calculations\nEarthing & Bonding Systems\nCircuit Breakers, RCDs & Surge Protection\nDistribution Board Design & Wiring\nInspection, Testing & Certification Procedures\nDocumentation & As-Built Drawings\nPractical Workshop: Complete Sub-Circuit Installation",
                'level' => Training::LEVEL_BEGINNER,
                'duration_weeks' => 3,
                'price' => 120000,
                'capacity' => 25,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'ref_id' => "TRG-{$year}-".str_pad($baseCount + 5, 6, '0', STR_PAD_LEFT),
                'title' => 'Solar Sales & Business Development',
                'description' => 'Learn to sell solar solutions effectively. Covers lead generation, site assessment for sales, proposal writing, financial modeling for customers, and closing techniques.',
                'curriculum' => "Solar Market Landscape in Nigeria\nCustomer Profiling & Lead Qualification\nSite Assessment for Sales Teams\nEnergy Consumption Analysis & Load Profiling\nProposal Writing & Presentation Skills\nFinancial Modeling: Payback, ROI, IRR\nOvercoming Objections & Closing Techniques\nAfter-Sales Service & Customer Retention\nDigital Marketing for Solar Business\nBuilding a Sustainable Solar Sales Pipeline",
                'level' => Training::LEVEL_BEGINNER,
                'duration_weeks' => 2,
                'price' => 80000,
                'capacity' => 30,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'ref_id' => "TRG-{$year}-".str_pad($baseCount + 6, 6, '0', STR_PAD_LEFT),
                'title' => 'Solar Pumping & Irrigation Systems',
                'description' => 'Specialized training on solar water pumping systems for agriculture and domestic use. Covers pump sizing, array design, and system installation.',
                'curriculum' => "Solar Pumping Fundamentals & Applications\nPump Types: Submersible, Surface, Centrifugal\nHydraulic Calculations: Head, Flow, Friction Losses\nSolar Array Sizing for Pumping Systems\nVariable Frequency Drives (VFD) & Controllers\nInstallation: Boreholes, Surface Water & Tanks\nSystem Protection: Dry-Run, Overvoltage, Surge\nMonitoring & Remote Control Systems\nEconomic Analysis: Diesel vs Solar Pumping\nCase Studies: Farm & Community Water Projects",
                'level' => Training::LEVEL_INTERMEDIATE,
                'duration_weeks' => 3,
                'price' => 160000,
                'capacity' => 15,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'ref_id' => "TRG-{$year}-".str_pad($baseCount + 7, 6, '0', STR_PAD_LEFT),
                'title' => 'Commercial & Industrial Solar EPC',
                'description' => 'End-to-end Engineering, Procurement & Construction training for large-scale solar projects. Covers project management, procurement, quality control, and commissioning.',
                'curriculum' => "C&I Solar Project Lifecycle & EPC Model\nFeasibility Studies & Site Assessment\nDetailed Engineering Design & Single-Line Diagrams\nProcurement Strategy & Vendor Management\nStructural & Civil Works for Ground Mount\nMedium Voltage Interconnection & Grid Studies\nQuality Assurance & Quality Control (QA/QC)\nHealth, Safety & Environment (HSE) Management\nCommissioning Tests: IV Curves, IR, Performance Ratio\nO&M Planning & Performance Guarantees",
                'level' => Training::LEVEL_ADVANCED,
                'duration_weeks' => 8,
                'price' => 400000,
                'capacity' => 12,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
            [
                'ref_id' => "TRG-{$year}-".str_pad($baseCount + 8, 6, '0', STR_PAD_LEFT),
                'title' => 'Solar Entrepreneurship & Business Setup',
                'description' => 'Complete guide to starting and running a solar business in Nigeria. Covers registration, licensing, supply chain, team building, and scaling.',
                'curriculum' => "Business Registration & Corporate Structure\nNigerian Renewable Energy Regulations & Incentives\nCOREN, SON & NERC Licensing Requirements\nBuilding a Supply Chain: Distributors & OEMs\nTeam Building: Hiring Technicians & Sales Staff\nFinancial Management & Pricing Strategies\nInsurance, Warranties & Legal Contracts\nMarketing Strategy & Brand Building\nScaling: Branches, Partnerships & Franchising\nAccess to Finance: Grants, Loans & Investors",
                'level' => Training::LEVEL_BEGINNER,
                'duration_weeks' => 2,
                'price' => 100000,
                'capacity' => 25,
                'is_active' => true,
                'created_by' => $admin?->id,
            ],
        ];

        foreach ($trainings as $trainingData) {
            $training = Training::create($trainingData);
            static::buildCurriculum($training);
        }
    }
}
