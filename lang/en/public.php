<?php

return [
    'nav' => [
        'aria' => 'Primary navigation',
        'services' => 'Services',
        'works' => 'Works',
        'about' => 'About',
        'faq' => 'FAQ',
        'login' => 'Log in',
        'dashboard' => 'Dashboard',
        'portal_login' => 'Client Portal Login',
        'start_project' => 'Start a Project',
        'privacy' => 'Privacy Policy',
        'terms' => 'Terms & Conditions',
    ],

    'seo' => [
        'home_title' => 'Quix Code — Integrated Software Agency & Real-Time Client Portal',
        'home_description' => 'Custom web engineering, business automation, and a dedicated client portal with real-time GitHub progress tracking and transparent billing.',
        'services_title' => 'Software Engineering Services — Quix Code',
        'services_description' => 'Custom Web Development, Business Automation, and Integrated Client Portals built for modern operations.',
        'works_title' => 'Portfolio & Case Studies — Quix Code',
        'works_description' => 'Selected case studies in web platforms, payment gateways, and operational automation engineered by Quix Code.',
        'about_title' => 'About & Engineering Principles — Quix Code',
        'about_description' => 'Learn how Quix Code combines backend rigor, real-time progress visibility, and data protection.',
        'faq_title' => 'Frequently Asked Questions — Quix Code',
        'faq_description' => 'Answers on project workflow, milestone billing, revision rounds, source code handover, and warranty.',
        'start_title' => 'Start a New Project — Quix Code',
        'start_description' => 'Submit your software brief and receive instant Client Portal access for quotation review and negotiation.',
        'privacy_title' => 'Privacy Policy (PDP Law) — Quix Code',
        'privacy_description' => 'How Quix Code collects, retains, and protects personal data in compliance with Indonesia PDP Law.',
        'terms_title' => 'Terms & Conditions — Quix Code',
        'terms_description' => 'Terms governing quotations, milestone billing, revision policy, intellectual property handover, and warranty.',
    ],

    'home' => [
        'badge' => 'Clean Tech Software Engineering · Integrated Client Portal',
        'headline' => 'Ship Production Software with Real-Time Delivery Visibility.',
        'subheadline' => 'From itemized quotations to automated GitHub commit progress, structured revision tickets, and multi-term invoicing inside a dedicated Client Portal.',
        'cta_primary' => 'Submit Project Brief',
        'cta_secondary' => 'Explore Case Studies',
        'pillars_title' => 'Three Core Engineering Pillars',
        'pillars_subtitle' => 'Built for teams that demand production reliability and full transparency throughout delivery.',
        'learn_more' => 'Explore service scope',
        'works_title' => 'Featured Case Studies',
        'works_subtitle' => 'Production systems tested, verified, and handed over through our client portal workflow.',
        'view_all_works' => 'View all works',
    ],

    'currency' => [
        'estimate_badge' => 'Estimate',
        'estimate_notice' => 'USD figures are display estimates based on recent exchange rates; binding contract totals are locked in the project currency.',
        'updated_at' => 'updated :time',
        'stale_warning' => 'Reference exchange rate is older than 48 hours',
    ],

    'services' => [
        'title' => 'Services & Engineering Scope',
        'subtitle' => 'Every engagement includes modular architecture, automated test coverage, and full Client Portal access.',
        'starting_from' => 'Starting from',
        'request_quote' => 'Request Quotation',
        'items' => [
            'web' => [
                'title' => 'Web Development & Custom SaaS',
                'summary' => 'High-throughput custom web applications built on the TALL Stack for internal operations and multi-role digital products.',
                'deliverables' => [
                    'Normalized database architecture & role-based access control',
                    'Operational dashboards, exports, and bilingual support (ID/EN)',
                    'Automated test suite & production-ready deployment pipeline',
                ],
            ],
            'automation' => [
                'title' => 'Business Automation & API Integrations',
                'summary' => 'Workflow automation, webhook pipelines, payment gateways, WhatsApp/Email alerts, and cross-system synchronization.',
                'deliverables' => [
                    'Idempotent background queues with retry backoff & monitoring',
                    'HMAC-verified webhook ingestion & dead-letter recovery',
                    'Append-only audit logging across every status and financial event',
                ],
            ],
            'portal' => [
                'title' => 'Client Portal & Delivery Management Systems',
                'summary' => 'Dedicated client workspaces for quotation negotiation, milestone sign-off, revision quotas, milestone billing, and handover.',
                'deliverables' => [
                    'Strict per-tenant data isolation enforced at query & policy levels',
                    'Multi-term invoicing (30-40-30 / 50-50) & bilingual PDF receipts',
                    'Protected handover vault unlocked upon final invoice settlement',
                ],
            ],
        ],
    ],

    'works' => [
        'title' => 'Portfolio & Case Studies',
        'subtitle' => 'Software systems engineered by Quix Code and published with explicit client authorization.',
        'empty_title' => 'No published case studies yet',
        'empty_description' => 'New project case studies are being prepared for publication. You can still submit your project brief today.',
        'read_case' => 'Read case study',
        'back_to_works' => 'Back to works',
        'overview_heading' => 'Solution Overview',
        'stack_heading' => 'Technology Stack',
        'cta_heading' => 'Planning a similar platform for your business?',
        'cta_body' => 'Share your technical goals and receive an itemized proposal from our engineering team.',
    ],

    'about' => [
        'title' => 'About Quix Code',
        'subtitle' => 'A software engineering studio pairing disciplined backend architecture with transparent client collaboration.',
        'story_title' => 'Replacing Guesswork with Measurable Delivery',
        'story_body' => 'Quix Code was built to eliminate black-box software development. Every client receives dedicated Client Portal access to review itemized quotations, watch real progress sync from the main Git branch, log structured revision tickets, and receive full handover archives upon completion.',
        'values' => [
            'transparency' => [
                'title' => 'Real-Time Transparency',
                'body' => 'Delivery progress updates directly from main-branch commits and milestone thresholds without opaque status reports.',
            ],
            'engineering' => [
                'title' => 'Disciplined Engineering',
                'body' => 'Every module is backed by server-side authorization policies, strict static analysis, and automated feature tests.',
            ],
            'security' => [
                'title' => 'Security & Data Privacy',
                'body' => 'Tenant query isolation, encrypted integration secrets, and auditable consent records aligned with data protection standards.',
            ],
        ],
    ],

    'faq' => [
        'title' => 'Frequently Asked Questions',
        'subtitle' => 'Key details on quotations, milestone payments, revision quotas, and post-launch warranty.',
        'items' => [
            [
                'q' => 'How does a project begin at Quix Code?',
                'a' => 'Submit your requirements through the Start a Project form. The platform provisions your Client Portal account and sends a signed password-setup link to your email. Once reviewed, we publish an itemized quotation inside your portal.',
            ],
            [
                'q' => 'How are payments structured?',
                'a' => 'Projects follow structured milestones (such as 30-40-30 or 50-50) via bank transfer with proof-of-payment upload in the portal. Development starts once the down payment is verified.',
            ],
            [
                'q' => 'How many free revision rounds are included?',
                'a' => 'Each milestone includes free revision rounds as specified in your quotation. New feature requests outside the agreed scope are handled transparently as paid Change Requests.',
            ],
            [
                'q' => 'When are source code and final archives delivered?',
                'a' => 'Once progress reaches 100% and the final invoice is verified as paid, the Handover vault unlocks in your Client Portal for secure archive download.',
            ],
            [
                'q' => 'Is post-launch bug warranty included?',
                'a' => 'Yes, every delivered project includes a 30-day warranty period covering bug fixes within the agreed specification.',
            ],
        ],
    ],

    'intake' => [
        'title' => 'Start a Project with Quix Code',
        'subtitle' => 'Tell us about your project goals. We will automatically create your Client Portal account so you can track your quotation.',
        'fields' => [
            'name' => 'Full name / PIC',
            'email' => 'Work or personal email',
            'whatsapp' => 'WhatsApp number',
            'service_type' => 'Service category',
            'title' => 'Project title',
            'description' => 'Project requirements & key objectives',
            'currency' => 'Preferred quotation currency',
            'budget_reference' => 'Estimated budget (optional)',
        ],
        'consent_prefix' => 'I consent to the processing of my personal data for quotation and portal account provisioning in accordance with the',
        'submit' => 'Submit Project Brief',
        'submitting' => 'Submitting brief…',
        'success_title' => 'Project Brief Received',
        'success_body' => 'Your project ":project" has been recorded. Please check :email for your secure Client Portal password setup link.',
        'back_home' => 'Back to Home',
        'errors' => [
            'disposable_email' => 'Please provide a permanent business or personal email address (disposable email domains are not accepted).',
            'rate_limited' => 'Too many submissions from your network. Please try again later.',
            'captcha_failed' => 'CAPTCHA security verification failed. Please reload and try again.',
            'duplicate_lead' => 'This email or WhatsApp number already has an open project inquiry. Please log in to the Client Portal to continue.',
        ],
    ],

    'legal' => [
        'version_label' => 'Document version: :version',
        'dpo_heading' => 'Data Protection Contact & Retention (PDP Law)',
        'dpo_body' => 'To request access, export, correction, or deletion of personal data under Indonesia Personal Data Protection Law, contact :email. Financial and contract records are retained for :years years for audit and tax compliance.',
        'privacy' => [
            'title' => 'Privacy Policy & Personal Data Protection',
            'sections' => [
                [
                    'heading' => '1. Personal Data We Collect',
                    'body' => 'We collect your name, email address, WhatsApp number, project brief details, campaign attribution parameters (UTM), and timestamped consent records when you submit an inquiry or use the Client Portal.',
                ],
                [
                    'heading' => '2. Purpose & Lawful Basis',
                    'body' => 'Personal data is processed strictly to prepare quotations, deliver contracted software projects, issue invoices, send transactional notifications, and fulfill statutory obligations.',
                ],
                [
                    'heading' => '3. Retention & Security Controls',
                    'body' => 'Raw technical webhook payloads are purged automatically after 90 days. Portal access is protected by tenant query isolation, encrypted secrets, and append-only audit logs.',
                ],
            ],
        ],
        'terms' => [
            'title' => 'Terms & Conditions of Service',
            'sections' => [
                [
                    'heading' => '1. Quotations & Currency Lock',
                    'body' => 'Formal quotations carry an explicit validity date. Once accepted inside the Client Portal, the contract price and project currency are locked for all subsequent invoices.',
                ],
                [
                    'heading' => '2. Milestone Payments & Revisions',
                    'body' => 'Engineering work commences once the down payment invoice is verified. Revisions are tracked via structured tickets within the free revision rounds defined in the quotation.',
                ],
                [
                    'heading' => '3. Handover & Warranty',
                    'body' => 'Full archive download access is granted upon settlement of the final invoice, followed by a 30-calendar-day bug-fix warranty.',
                ],
            ],
        ],
    ],

    'errors' => [
        'back_home' => 'Back to Home',
        '404' => [
            'title' => 'Page Not Found',
            'body' => 'The page you requested may have been moved or is no longer available.',
        ],
        '500' => [
            'title' => 'Server Error',
            'body' => 'Our engineering team has been notified automatically. Please try again shortly.',
        ],
        '503' => [
            'title' => 'Scheduled Maintenance',
            'body' => 'We are performing a scheduled platform update. Please check back in a few minutes.',
        ],
    ],

    'footer' => [
        'tagline' => 'Modern software engineering agency with an integrated Client Portal for real-time delivery, billing, and handover.',
        'navigation' => 'Navigation',
        'legal' => 'Legal & Compliance',
        'rights' => 'All rights reserved.',
    ],
];
