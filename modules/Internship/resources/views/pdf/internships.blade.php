<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ __('internship::ui.program_title') }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 11px;
            margin: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
            border-bottom: 2px solid #10b981;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 20px;
            color: #065f46;
            margin: 0;
            text-transform: uppercase;
        }
        .header p {
            font-size: 12px;
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
            padding: 10px 8px;
            text-align: left;
        }
        .table th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .table tr:nth-child(even) {
            background-color: #fcfcfc;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 10px;
            color: #9ca3af;
        }
        .text-center {
            text-align: center !important;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('internship::ui.program_title') }}</h1>
        <p>{{ $school?->name ?? setting('brand_name', setting('app_name')) }}</p>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th width="5%" class="text-center">No</th>
                <th width="35%">{{ __('internship::ui.title') }}</th>
                <th width="10%" class="text-center">{{ __('internship::ui.year') }}</th>
                <th width="20%">{{ __('internship::ui.semester') }}</th>
                <th width="15%" class="text-center">{{ __('internship::ui.date_start') }}</th>
                <th width="15%" class="text-center">{{ __('internship::ui.date_finish') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($records as $index => $record)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $record->title }}</strong>
                        @if($record->description)
                            <br><small style="color: #6b7280;">{{ Str::limit($record->description, 50) }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $record->year }}</td>
                    <td>{{ $record->semester }}</td>
                    <td class="text-center">{{ $record->date_start?->translatedFormat('d M Y') ?? '-' }}</td>
                    <td class="text-center">{{ $record->date_finish?->translatedFormat('d M Y') ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>{{ __('ui::common.generated_at') }}: {{ $date }}</p>
    </div>
</body>
</html>
