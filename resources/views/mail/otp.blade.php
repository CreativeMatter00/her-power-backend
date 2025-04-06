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
            max-width: 600px;
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
    <div style="width: 100%; max-width: 1140px; padding-left: 15px;padding-right: 15px;margin-left: auto; margin-right: auto; ">
        <div class="email-container">
            <h2 style="text-align: center">Welcome to Her Power</h2>
            <h3 style="text-align: center">OTP Verification</h3>
            <p>Hello User,</p>
            <p>We received a request to verify your identity. Use the OTP (One Time Password) below to complete your
                verification process:</p>

            <div style="text-align: center; font-size: 2rem; font-weight: bold; color: #333; padding: 20px; margin-top:10px; margin-bottom:10px;">
                <strong>{{ $mailData['OTP'] }}</strong>
            </div>

            <p>This will expire after 5 minutes. If you did not request this, please ignore this email or contact support.</p>

            <div class="footer">
                <p>Thank you for using our service!</p>
                <p>&copy; {{ date('Y') }} Her Power. All rights reserved.</p>
                <p><a href="http://163.47.146.233:4021/en/contact">Contact Support</a></p>
            </div>
        </div>
    </div>
</body>

</html>
