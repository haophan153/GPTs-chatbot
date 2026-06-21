<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ma xac nhan huy booking</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="background: #f8f9fa; border-radius: 8px; padding: 30px;">
        <h2 style="color: #2c3e50; margin-top: 0;">Xac nhan huy booking</h2>

        <p>Xin chao,</p>

        <p>Ban yeu cau huy booking <strong>{{ $booking->booking_code }}</strong>.
        Vui long su dung ma OTP ben duoi de xac nhan viec huy:</p>

        <div style="background: #fff; border: 2px dashed #3498db; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;">
            <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #2c3e50;">{{ $otp->otp_code }}</div>
        </div>

        <p style="color: #e74c3c; font-size: 14px;">
            <strong>Ma nay co hieu luc trong 5 phut.</strong>
        </p>

        <p style="font-size: 14px; color: #7f8c8d;">
            Neu ban khong yeu cau huy booking, vui long bo qua email nay.
        </p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">

        <p style="font-size: 12px; color: #95a5a6;">
            Email nay duoc gui tu he thong VJP Booking.
        </p>
    </div>
</body>
</html>
