<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kode OTP Reset Password</title>
</head>

<body style="font-family: Arial, sans-serif; background: #f6f0e6; padding: 24px; color: #2f241d;">
    <div style="max-width: 520px; margin: 0 auto; background: #ffffff; border: 1px solid #e3ccb0; border-radius: 14px; padding: 28px;">
        <h2 style="margin-top: 0; color: #a64b35;">Bali Culinary Guide</h2>
        <p>Kami menerima permintaan untuk mereset password akun Anda. Gunakan kode OTP berikut untuk melanjutkan:</p>

        <div style="margin: 22px 0; padding: 16px; background: #fbf3e4; border: 1px dashed #c98672; border-radius: 10px; text-align: center;">
            <span style="font-size: 30px; font-weight: 700; letter-spacing: 8px; color: #4c2d1f;">{{ $otpCode }}</span>
        </div>

        <p style="font-size: 13.5px; color: #6c5a4c;">
            Kode ini berlaku selama <strong>{{ $expiryMinutes }} menit</strong> dan hanya dapat digunakan <strong>satu kali</strong>.
            Jangan bagikan kode ini kepada siapa pun.
        </p>

        <p style="font-size: 12.5px; color: #6c5a4c; margin-bottom: 0;">
            Jika Anda tidak merasa meminta reset password, abaikan email ini.
        </p>
    </div>
</body>

</html>
