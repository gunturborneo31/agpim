<?php

return [
    'priorities' => [
        'tinggi' => ['label' => 'Tinggi', 'color' => 'rose'],
        'sedang' => ['label' => 'Sedang', 'color' => 'amber'],
        'biasa' => ['label' => 'Biasa', 'color' => 'sky'],
    ],
    'statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Diajukan',
        'under_review' => 'Verifikasi Prokopim',
        'awaiting_disposition' => 'Menunggu Disposisi',
        'approved' => 'Disetujui',
        'needs_revision' => 'Perlu Revisi',
        'rejected' => 'Ditolak',
        'completed' => 'Selesai',
    ],
    'attendance_sources' => [
        'proposed' => 'Orang yang diajukan OPD',
        'disposition' => 'Orang hasil disposisi',
    ],
    'dispositions' => [
        'bupati' => 'Dihadiri Bupati',
        'wakil_bupati' => 'Dihadiri Wakil Bupati',
        'sekda' => 'Dihadiri Sekda',
        'other_official' => 'Dihadiri Pejabat Lain',
        'rejected' => 'Ditolak',
    ],
    'quick_tabs' => [
        'Hari Ini',
        'Besok',
        'Lusa',
        'Minggu Ini',
    ],
    'notification_channels' => ['in_app', 'email', 'whatsapp'],
];
