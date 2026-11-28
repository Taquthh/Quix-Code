<?php

return [
    'title' => 'Notifications',
    'subtitle' => 'Latest news about your projects, invoices, and requests.',
    'bell_label' => '{0} Notifications, none unread|{1} Notifications, 1 unread|[2,*] Notifications, :count unread',
    'unread' => 'Unread',
    'mark_read' => 'Mark as read',
    'mark_all_read' => 'Mark all as read',
    'empty' => 'No notifications yet',
    'empty_hint' => 'News about quotations, invoices, and replies from the team will appear here.',
    'unknown' => 'New update',

    'items' => [
        'quotation_sent' => [
            'title' => 'New quotation v:version',
            'body' => 'Project :project: total :amount. Please review it.',
        ],
        'quotation_expiring' => [
            'title' => 'Quotation expires soon',
            'body' => 'Quotation v:version for :project is about to expire.',
        ],
        'dp_invoice' => [
            'title' => 'Down payment invoice :number issued',
            'body' => 'Project :project: :amount. Work starts once the down payment is verified.',
        ],
        'invoice_issued' => [
            'title' => 'Invoice :number issued',
            'body' => 'Project :project: :amount.',
        ],
        'invoice_due_soon' => [
            'title' => 'Invoice :number is due soon',
            'body' => 'Project :project: :amount is still unpaid.',
        ],
        'invoice_due_today' => [
            'title' => 'Invoice :number is due today',
            'body' => 'Project :project: :amount is still unpaid.',
        ],
        'invoice_overdue' => [
            'title' => 'Invoice :number is overdue',
            'body' => 'Project :project: :amount is unpaid and past its due date.',
        ],
        'payment_verified' => [
            'title' => 'Payment for invoice :number received',
            'body' => 'Project :project: :amount has been verified.',
        ],
        'payment_rejected' => [
            'title' => 'Proof of payment for invoice :number rejected',
            'body' => 'Reason: :reason. Please upload the correct proof again.',
        ],
        'ticket_replied' => [
            'title' => 'New reply to ":subject"',
            'body' => 'The team replied to your request in :project.',
        ],
    ],
];
