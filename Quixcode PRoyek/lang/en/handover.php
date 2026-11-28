<?php

return [
    'title' => 'Handover & files',
    'back' => 'Back to project',
    'status_heading' => 'Handover status',
    'view_invoices' => 'View invoices',
    'files_heading' => 'Handover files',
    'files_empty' => 'No files yet',
    'files_empty_hint' => 'Our team will upload your handover files here.',
    'file_label' => ':type file',
    'download' => 'Download',
    'method' => 'Handover method',
    'expires' => 'valid until :date',
    'expired' => 'validity has ended',

    'states' => [
        'not_started' => [
            'title' => 'Not started',
            'body' => 'Handover becomes available once the work is finished. You will be told when it is time.',
        ],
        'no_final_invoice' => [
            'title' => 'Locked: final invoice not issued yet',
            'body' => 'Handover files unlock once the final invoice is issued and paid. Our team will issue it when the work is done.',
        ],
        'final_unpaid' => [
            'title' => 'Locked: final invoice not paid yet',
            'body' => 'Handover files unlock automatically once the final invoice is paid and the payment is verified.',
        ],
        'ready' => [
            'title' => 'Ready to download',
            'body' => 'The final invoice is paid. Your handover files are available below.',
        ],
    ],
];
