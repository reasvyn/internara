<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #4f46e5; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background: #f9fafb; }
        .footer { padding: 20px; text-align: center; font-size: 12px; color: #6b7280; }
        .button { display: inline-block; padding: 10px 20px; background: #4f46e5; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Internara Notification</h1>
        </div>
        <div class="content">
            <h2>Hello {{ $user->name }},</h2>
            <p>{{ $body }}</p>
            @if(isset($actionUrl))
                <p style="text-align: center; margin: 30px 0;">
                    <a href="{{ $actionUrl }}" class="button">{{ $actionText ?? 'View Details' }}</a>
                </p>
            @endif
        </div>
        <div class="footer">
            <p>This email was sent from Internara System</p>
            <p>If you don't want to receive these emails, you can update your notification preferences.</p>
        </div>
    </div>
</body>
</html>
