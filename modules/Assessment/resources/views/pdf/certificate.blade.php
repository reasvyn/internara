<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Internship Certificate</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            height: 100%;
            padding: 40px;
            border: 15px solid #1e3a8a;
            box-sizing: border-box;
            position: relative;
        }
        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        .header h1 {
            font-size: 48px;
            color: #1e3a8a;
            margin: 0;
            text-transform: uppercase;
        }
        .header p {
            font-size: 18px;
            color: #666;
            margin-top: 5px;
        }
        .content {
            text-align: center;
            margin-bottom: 50px;
        }
        .content p {
            font-size: 20px;
            margin: 10px 0;
        }
        .student-name {
            font-size: 36px;
            font-weight: bold;
            color: #111;
            text-decoration: underline;
            margin: 20px 0;
        }
        .details {
            margin: 30px auto;
            width: 80%;
            border-top: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
            padding: 20px 0;
        }
        .footer {
            margin-top: 80px;
        }
        .signatures {
            width: 100%;
        }
        .signatures td {
            text-align: center;
            width: 50%;
        }
        .signature-line {
            width: 200px;
            border-bottom: 1px solid #333;
            margin: 0 auto 10px;
        }
        .qr-code {
            position: absolute;
            bottom: 40px;
            right: 40px;
            text-align: center;
        }
        .qr-code img {
            width: 100px;
            height: 100px;
        }
        .qr-code p {
            font-size: 10px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Certificate of Completion</h1>
            <p>Internara Internship Management System</p>
        </div>

        <div class="content">
            <p>This is to certify that</p>
            <div class="student-name">{{ $student->name }}</div>
            <p>has successfully completed the internship program at</p>
            <p><strong>{{ $registration->company_name ?? 'Partner Industry' }}</strong></p>
            
            <div class="details">
                <p>Period: {{ $registration->start_date?->format('d M Y') ?? '-' }} to {{ $registration->end_date?->format('d M Y') ?? '-' }}</p>
                <p>Status: <strong>COMPLETED</strong></p>
            </div>
        </div>

        <div class="footer">
            <table class="signatures">
                <tr>
                    <td>
                        <div class="signature-line"></div>
                        <p>Industry Mentor</p>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <p>School Supervisor</p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="qr-code">
            <img src="data:image/png;base64, {{ $qrCode }}">
            <p>Scan to verify authenticity</p>
        </div>
    </div>
</body>
</html>
