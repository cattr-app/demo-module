<?php

namespace Modules\Demo\Support;

/**
 * Content of the demo company: projects with tasks, the team that works on them, and task comments.
 * Estimates are in hours.
 */
class DemoCatalog
{
    /** Activity ranges [min, max] of keyboard and mouse fill, percent */
    public const ACTIVITY_PROFILES = [
        'developer' => ['keyboard' => [45, 85], 'mouse' => [15, 45]],
        'designer' => ['keyboard' => [10, 35], 'mouse' => [55, 90]],
        'support' => ['keyboard' => [35, 65], 'mouse' => [30, 60]],
        'manager' => ['keyboard' => [20, 50], 'mouse' => [30, 60]],
    ];

    public const PROJECTS = [
        'mobile' => [
            'name' => 'Mobile App',
            'description' => 'iOS and Android client: new onboarding, offline mode and push notifications.',
            'tasks' => [
                ['Redesign the onboarding flow', 16],
                ['Offline mode for the task list', 24],
                ['Push notifications for comments', 12],
                ['Fix crash on Android 15 when rotating', 4],
                ['Dark theme', 10],
                ['Prepare the App Store release', 6],
                ['Biometric sign-in', 8],
            ],
        ],
        'website' => [
            'name' => 'Website Redesign',
            'description' => 'New marketing website: design system, landing pages and performance.',
            'tasks' => [
                ['Design system: colors and typography', 12],
                ['Pricing page', 8],
                ['Hero section animation', 6],
                ['Blog templates', 10],
                ['Improve Lighthouse score', 8],
                ['Contact form with spam protection', 5],
                ['Localize the website', 14],
            ],
        ],
        'support' => [
            'name' => 'Customer Support',
            'description' => 'Help desk, knowledge base and customer onboarding calls.',
            'tasks' => [
                ['Answer help desk tickets', null],
                ['Update the knowledge base', 12],
                ['Onboarding calls with new customers', null],
                ['Monthly support report', 4],
                ['Video tutorials for the desktop app', 16],
            ],
        ],
        'infrastructure' => [
            'name' => 'Infrastructure',
            'description' => 'Servers, CI/CD, monitoring and backups.',
            'tasks' => [
                ['Migrate CI to the new runners', 10],
                ['Set up alerting for the API', 6],
                ['Database backup verification', 4],
                ['Upgrade Kubernetes cluster', 12],
                ['Reduce cloud costs', 8],
                ['Move screenshots to S3', 10],
            ],
        ],
        'marketing' => [
            'name' => 'Marketing',
            'description' => 'Campaigns, social media and the product newsletter.',
            'tasks' => [
                ['Plan the autumn campaign', 8],
                ['Write the product newsletter', 5],
                ['Social media content for the month', 10],
                ['Case study with a customer', 8],
                ['Webinar: getting started with Cattr', 6],
            ],
        ],
    ];

    /**
     * Demo login accounts: their names, projects and activity profile.
     * They work in the application timezone.
     */
    public const ACCOUNT_PERSONAS = [
        'admin' => ['name' => 'Alex Morgan', 'projects' => [], 'profile' => 'manager'],
        'manager' => ['name' => 'Sarah Johnson', 'projects' => ['marketing', 'support'], 'profile' => 'manager'],
        'auditor' => ['name' => 'Michael Brown', 'projects' => ['infrastructure'], 'profile' => 'manager'],
        'project_manager' => ['name' => 'Emma Davis', 'projects' => ['mobile', 'website'], 'profile' => 'manager'],
        'project_auditor' => ['name' => 'James Miller', 'projects' => ['website'], 'profile' => 'designer'],
        'user' => ['name' => 'Chris Taylor', 'projects' => ['mobile'], 'profile' => 'developer'],
    ];

    /**
     * Team members without a login, spread over timezones so that someone works at any hour.
     * `shift` is the local hour the working day starts at.
     */
    public const TEAM = [
        'emily.carter' => ['name' => 'Emily Carter', 'timezone' => 'America/Los_Angeles', 'shift' => 9, 'projects' => ['mobile'], 'profile' => 'developer'],
        'sofia.martinez' => ['name' => 'Sofia Martinez', 'timezone' => 'America/Mexico_City', 'shift' => 10, 'projects' => ['marketing'], 'profile' => 'manager'],
        'lucas.oliveira' => ['name' => 'Lucas Oliveira', 'timezone' => 'America/Sao_Paulo', 'shift' => 9, 'projects' => ['website'], 'profile' => 'designer'],
        'daniel.brooks' => ['name' => 'Daniel Brooks', 'timezone' => 'America/New_York', 'shift' => 8, 'projects' => ['support'], 'profile' => 'support'],
        'olivia.bennett' => ['name' => 'Olivia Bennett', 'timezone' => 'Europe/London', 'shift' => 9, 'projects' => ['marketing', 'website'], 'profile' => 'manager'],
        'anna.schmidt' => ['name' => 'Anna Schmidt', 'timezone' => 'Europe/Berlin', 'shift' => 8, 'projects' => ['website'], 'profile' => 'developer'],
        'ivan.petrov' => ['name' => 'Ivan Petrov', 'timezone' => 'Europe/Moscow', 'shift' => 10, 'projects' => ['mobile'], 'profile' => 'developer'],
        'aisha.rahman' => ['name' => 'Aisha Rahman', 'timezone' => 'Asia/Dubai', 'shift' => 9, 'projects' => ['support'], 'profile' => 'support'],
        'priya.sharma' => ['name' => 'Priya Sharma', 'timezone' => 'Asia/Kolkata', 'shift' => 10, 'projects' => ['infrastructure'], 'profile' => 'developer'],
        'alexei.smirnov' => ['name' => 'Alexei Smirnov', 'timezone' => 'Asia/Novosibirsk', 'shift' => 9, 'projects' => ['infrastructure'], 'profile' => 'developer'],
        'mei.lin' => ['name' => 'Mei Lin', 'timezone' => 'Asia/Singapore', 'shift' => 9, 'projects' => ['website', 'marketing'], 'profile' => 'designer'],
        'kenji.tanaka' => ['name' => 'Kenji Tanaka', 'timezone' => 'Asia/Tokyo', 'shift' => 9, 'projects' => ['mobile'], 'profile' => 'developer'],
        'jack.wilson' => ['name' => 'Jack Wilson', 'timezone' => 'Australia/Sydney', 'shift' => 8, 'projects' => ['support'], 'profile' => 'support'],
        'noah.kim' => ['name' => 'Noah Kim', 'timezone' => 'Pacific/Auckland', 'shift' => 8, 'projects' => ['infrastructure', 'mobile'], 'profile' => 'developer'],
    ];

    public const COMMENTS = [
        'Started on this, will share a draft by the end of the day.',
        'Pushed the first version, please take a look.',
        'Blocked: waiting for the API changes.',
        'Looks good to me, a couple of small notes in the doc.',
        'Moved the deadline by two days, the scope grew.',
        'Done on my side, ready for review.',
        'Can we discuss this at the stand-up?',
        'Added screenshots and steps to reproduce.',
    ];

    /**
     * Code names of releases, sprints and features: "{adjective} {noun}".
     */
    public const CODENAME_ADJECTIVES = [
        'Agile', 'Bold', 'Brainy', 'Bright', 'Brisk', 'Calm', 'Clever', 'Daring', 'Eager', 'Fierce',
        'Floral', 'Fjord', 'Grand', 'Green', 'Icy', 'Keen', 'Lively', 'Lunar', 'Lush', 'Meadow',
        'Nimble', 'Noble', 'Nordic', 'Polar', 'Rapid', 'Rugged', 'Sharp', 'Silent', 'Snowy', 'Swift',
        'Viking', 'Vivid', 'Winter', 'Witty',
    ];

    public const CODENAME_NOUNS = [
        'Andromeda', 'Aquila', 'Aster', 'Atlas', 'Aurora', 'Birch', 'Callisto', 'Cassiopeia', 'Cedar',
        'Cepheus', 'Clover', 'Cygnus', 'Dahlia', 'Draco', 'Echo', 'Europa', 'Fenrir', 'Fjell', 'Fjord',
        'Frost', 'Ganymede', 'Glacier', 'Granite', 'Harbor', 'Heather', 'Helios', 'Hyperion', 'Iris',
        'Lily', 'Loki', 'Lotus', 'Lyra', 'Midgard', 'Moss', 'Nansen', 'Nova', 'Orchid', 'Orion', 'Pearl',
        'Pegasus', 'Peony', 'Perseus', 'Phoenix', 'Poppy', 'Raven', 'Reef', 'Rigel', 'Rose', 'Selene',
        'Sif', 'Skadi', 'Sleipnir', 'Solstice', 'Spruce', 'Thor', 'Tundra', 'Vega', 'Ymir',
    ];

    /** Task name templates with a code name ({name}), estimates in hours */
    public const CODENAME_TASKS = [
        ['Release "{name}": final QA', 8],
        ['Sprint "{name}": planning', 2],
        ['Sprint "{name}": retrospective', 1],
        ['Feature "{name}": research and prototype', 12],
        ['Release notes for "{name}"', 3],
        ['Roll out "{name}" behind a feature flag', 5],
    ];

    public static function teamEmail(string $key): string
    {
        return $key . '@team.demo.cattr.app';
    }
}
