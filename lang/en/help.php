<?php

return [
    'title' => 'Help',
    'subtitle' => 'Short answers, and how to reach the team without leaving the portal.',
    'back' => 'Back to projects',
    'link' => 'Help',
    'onboarding' => [
        'heading' => 'Your first steps',
        'intro' => 'Three steps from quotation to a project under way.',
        'state' => [
            'done' => 'done',
            'current' => 'current step',
            'upcoming' => 'next',
        ],
        'steps' => [
            'approve_quotation' => [
                'title' => 'Approve the quotation',
                'description' => 'Review the details and price, discuss in chat if needed, then approve.',
                'action' => 'Open quotation',
            ],
            'pay_dp' => [
                'title' => 'Pay the down payment',
                'description' => 'The down payment invoice is issued automatically after approval. Pay it, then upload your proof of payment.',
                'action' => 'Open invoices',
            ],
            'track_progress' => [
                'title' => 'Track progress',
                'description' => 'See milestones, progress, and work updates at any time in the project overview.',
                'action' => 'Open project overview',
            ],
        ],
    ],
    'faq_heading' => 'Frequently asked questions',
    'faq' => [
        [
            'question' => 'How do I approve a quotation?',
            'answer' => 'Open Quotation in your project, review the details, then choose approve and confirm the approval statement. If anything needs to change, write it in the chat on the same page.',
        ],
        [
            'question' => 'How do I pay the down payment?',
            'answer' => 'Once the quotation is approved, the down payment invoice appears under Invoices. Pay as instructed on the invoice, then upload your proof of payment on the same page.',
        ],
        [
            'question' => 'How do I know my proof of payment was received?',
            'answer' => 'The team verifies your proof of payment. The result shows in the notification bell and is sent by email. If it is rejected, the reason is shown and you can upload it again.',
        ],
        [
            'question' => 'Where do I track project progress?',
            'answer' => 'In the project overview: milestones, progress percentage, and the work timeline. When a milestone is finished, you can accept it from that page.',
        ],
        [
            'question' => 'How do I request a revision or report a bug?',
            'answer' => 'Use the request form in the project overview and attach a screenshot if you have one. Each milestone includes :rounds free revision rounds; revisions beyond that are marked as paid (Change Request). Bug reports do not use the allowance.',
        ],
        [
            'question' => 'How do I change my language, display currency, or WhatsApp notifications?',
            'answer' => 'Open your account settings. The display currency is only an estimate; official quotation and invoice amounts always use the project currency. WhatsApp is sent only if you agree to it.',
        ],
    ],
    'support_heading' => 'Contact support',
    'support_intro' => 'The fastest way is the chat on your project. The team replies there and you get a notification.',
    'chat_project' => 'Chat with the team: :project',
    'no_active_project' => 'No active project yet. Your projects will appear here, with a chat to the team.',
    'other_channels' => 'Other channels',
];
