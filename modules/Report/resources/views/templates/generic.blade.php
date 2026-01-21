<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .title { font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .footer { position: fixed; bottom: 0; width: 100%; text-align: right; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { bg-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $title }}</div>
        <div>Internara Internship Management System</div>
    </div>

    <div class="content">
        @if(isset($data['rows']))
            <table>
                <thead>
                    <tr>
                        @foreach($data['headers'] as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($data['rows'] as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $row[$cell] ?? $cell }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="footer">
        Generated on {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
