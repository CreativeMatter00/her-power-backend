<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <style>
        body {
            background-color: #f4f7fa;
        }

        .email-container {
            max-width: 100%;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
        }

        .footer {
            font-size: 0.9rem;
            color: #777;
            text-align: center;
            margin-top: 20px;
        }

        .footer a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div style="width: 100%; padding-left: 15px; padding-right: 15px;">
        <div class="email-container">
            <h2 style="text-align: center">Welcome to Her Power</h2>
            <h3 style="text-align: center">{{ $user_type }} Approval Response</h3>
            <p>Hello {{ $user_info->name }},</p>

            <p>
                We are pleased to inform you that, Your approval request has been <strong>{{ $approve_status }}</strong> by the Administrator.
            </p>
            <br />
            <p>If your need any help, please contact our support team.</p>
            <br />
            <p>Thanks & Regards <br /> Her Power Family</p>
            <div class="footer">
                <p>Thank you for using our service!</p>
                <p>&copy; {{ date('Y') }} Her Power. All rights reserved.</p>
                <p><a href="http://163.47.146.233:4021/en/contact">Contact Support</a></p>
            </div>
        </div>
    </div>
</body>

</html>
