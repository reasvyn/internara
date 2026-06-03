<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Logbook Report</title>
    <style>
        body { font-family: sans-serif; font-size: 11pt; color: #333; line-height: 1.5; }
        h1 { font-size: 18pt; text-align: center; margin-bottom: 4pt; }
        h2 { font-size: 14pt; margin-top: 24pt; margin-bottom: 8pt; border-bottom: 1px solid #ccc; padding-bottom: 4pt; }
        .subtitle { text-align: center; font-size: 10pt; color: #666; margin-bottom: 20pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 12pt; }
        th, td { border: 1px solid #ccc; padding: 6pt 8pt; text-align: left; vertical-align: top; }
        th { background: #f5f5f5; font-size: 10pt; font-weight: bold; }
        td { font-size: 10pt; }
        .header-block { margin-bottom: 20pt; }
        .header-block table { border: none; margin: 0; }
        .header-block td { border: none; padding: 2pt 8pt; font-size: 10pt; }
        .header-block td:first-child { font-weight: bold; width: 140pt; }
        .note { font-size: 9pt; color: #555; font-style: italic; margin-top: 4pt; }
        .page-break { page-break-before: always; }
        .photo { max-width: 240px; max-height: 180px; margin-top: 4pt; }
        .footer { margin-top: 30pt; font-size: 9pt; color: #999; text-align: center; }
    </style>
</head>
<body>
    <h1>{{ __('logbook.report_title') }}</h1>
    <p class="subtitle">{{ __('logbook.report_subtitle') }}</p>

    <div class="header-block">
        <table>
            <tr><td>{{ __('logbook.report_student') }}</td><td>{{ $student->name }}</td></tr>
            @if($company)
                <tr><td>{{ __('logbook.report_company') }}</td><td>{{ $company->name }}</td></tr>
            @endif
            <tr><td>{{ __('logbook.report_period') }}</td><td>{{ $registration->start_date?->format('d M Y') }} — {{ $registration->end_date?->format('d M Y') }}</td></tr>
            <tr><td>{{ __('logbook.report_entries') }}</td><td>{{ $entries->count() }} {{ __('logbook.report_entries_count') }}</td></tr>
        </table>
    </div>

    @if($entries->isEmpty())
        <p style="text-align:center;color:#999;margin-top:40pt;">{{ __('logbook.report_no_entries') }}</p>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:60pt;">{{ __('logbook.date') }}</th>
                    <th>{{ __('logbook.content') }}</th>
                    <th>{{ __('logbook.learning_outcomes') }}</th>
                    @if($includeSupervisorNotes)
                        <th>{{ __('logbook.supervisor_note') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                    <tr>
                        <td style="white-space:nowrap;">{{ $entry->date->format('d M Y') }}</td>
                        <td>{{ $entry->content }}</td>
                        <td>{{ $entry->learning_outcomes ?? '—' }}</td>
                        @if($includeSupervisorNotes)
                            <td>{{ $entry->supervisor_note ?? '—' }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        {{ __('logbook.report_generated') }}: {{ now()->format('d M Y H:i') }}
    </div>
</body>
</html>
