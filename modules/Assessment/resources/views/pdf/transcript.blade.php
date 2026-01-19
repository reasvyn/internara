<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Internship Transcript</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            font-size: 12px;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #1e3a8a;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 24px;
            color: #1e3a8a;
            margin: 0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 30px;
        }
        .info-table td {
            padding: 5px;
        }
        .score-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .score-table th, .score-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: left;
        }
        .score-table th {
            background-color: #f3f4f6;
            color: #1e3a8a;
        }
        .text-center {
            text-align: center !important;
        }
        .text-right {
            text-align: right !important;
        }
        .final-grade {
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            text-align: right;
        }
        .footer {
            margin-top: 50px;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Official Internship Transcript</h1>
        <p>Internara Internship Management System</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="15%"><strong>Student Name</strong></td>
            <td width="35%">: {{ $student->name }}</td>
            <td width="15%"><strong>Student ID</strong></td>
            <td width="35%">: {{ $student->username }}</td>
        </tr>
        <tr>
            <td><strong>Company</strong></td>
            <td>: {{ $registration->company_name ?? 'Partner Industry' }}</td>
            <td><strong>Period</strong></td>
            <td>: {{ $registration->start_date?->format('M Y') ?? '-' }} - {{ $registration->end_date?->format('M Y') ?? '-' }}</td>
        </tr>
    </table>

    <h3>Assessment Breakdown</h3>
    <table class="score-table">
        <thead>
            <tr>
                <th width="10%">No</th>
                <th width="60%">Assessment Criteria</th>
                <th width="30%" class="text-center">Score</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="text-center">1</td>
                <td>Industry Mentor Evaluation</td>
                <td class="text-center">{{ $scoreCard['mentor']?->score ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="text-center">2</td>
                <td>School Supervisor Evaluation</td>
                <td class="text-center">{{ $scoreCard['teacher']?->score ?? 'N/A' }}</td>
            </tr>
        </tbody>
    </table>

    <div class="final-grade">
        Final Grade: {{ number_format($scoreCard['final_grade'] ?? 0, 2) }}
    </div>

    <div class="footer">
        <p>Issued on: {{ $date }}</p>
        <br><br><br>
        <p><strong>Internship Coordinator</strong></p>
    </div>
</body>
</html>
