<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('internship::ui.registration_title') }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 10px;
            margin: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #10b981;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18px;
            color: #065f46;
            margin: 0;
            text-transform: uppercase;
        }
        .header p {
            font-size: 11px;
            margin: 5px 0 0;
            color: #6b7280;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .table th, .table td {
            border: 1px solid #e5e7eb;
            padding: 8px 6px;
            text-align: left;
        }
        .table th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
        }
        .table tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 9px;
            color: #9ca3af;
        }
        .text-center {
            text-align: center !important;
        }
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .badge-success { background-color: #d1fae5; color: #065f46; }
        .badge-warning { background-color: #fef3c7; color: #92400e; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; }
        .badge-info { background-color: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('internship::ui.registration_title') }}</h1>
        <p>{{ setting('brand_name', setting('app_name')) }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="20%">{{ __('internship::ui.student') }}</th>
                <th width="20%">{{ __('internship::ui.program') }}</th>
                <th width="20%">{{ __('internship::ui.placement') }}</th>
                <th width="15%">{{ __('internship::ui.teacher') }}</th>
                <th width="10%" class="text-center">{{ __('internship::ui.status') }}</th>
                <th width="10%" class="text-center">{{ __('ui::common.created_at') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $index => $record)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td><strong>{{ $record->student->name }}</strong><br><small>{{ $record->student->username }}</small></td>
                    <td>{{ $record->internship->title }}</td>
                    <td>{{ $record->placement?->company?->name ?? 'N/A' }}</td>
                    <td>{{ $record->teacher?->name ?? 'N/A' }}</td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ $record->latestStatus()?->name ?? 'pending' }}</span>
                    </td>
                    <td class="text-center">{{ $record->created_at->format('d/m/y') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('ui::common.generated_at') }}: {{ $date }}</p>
    </div>
</body>
</html>
