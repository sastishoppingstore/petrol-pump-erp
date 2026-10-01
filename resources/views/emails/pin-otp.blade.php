<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PIN Login Code</title>
</head>
<body style="margin:0; padding:24px; background:#f1f5f9; font-family:Arial, Helvetica, sans-serif;">
    <div style="max-width:520px; margin:0 auto; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(2,6,23,.12);">
        <div style="background:linear-gradient(135deg,#020617,#0f172a); padding:24px; text-align:center; color:#ffffff;">
            <div style="font-size:30px;">⛽</div>
            <div style="font-size:18px; font-weight:800; letter-spacing:.5px;">{{ $stationName }}</div>
            <div style="font-size:12px; color:#cbd5e1; margin-top:4px;">PIN Login Verification / پن لاگ اِن تصدیق</div>
        </div>

        <div style="padding:28px 24px; text-align:center; color:#1e293b;">
            <p style="font-size:14px; margin:0 0 6px;">Assalam-o-Alaikum <strong>{{ $user->name }}</strong>,</p>
            <p style="font-size:13px; line-height:1.6; color:#475569; margin:0 0 20px;">
                Your PIN login code for shift login is below. / آپ کا پن لاگ اِن کوڈ نیچے دیا گیا ہے۔
                This code expires in <strong>{{ $expiryMinutes }} minutes</strong>.
            </p>

            <div style="display:inline-block; background:#fef2f2; border:2px dashed #dc2626; border-radius:12px; padding:14px 36px;">
                <div style="font-size:34px; font-weight:900; letter-spacing:10px; color:#b91c1c; font-family:'Courier New', monospace;">{{ $otp }}</div>
            </div>

            <p style="font-size:12px; line-height:1.7; color:#64748b; margin:22px 0 0;">
                Never share this code with anyone — not even station staff.
                <br>یہ کوڈ کسی کے ساتھ شیئر نہ کریں — اسٹیشن عملے کے ساتھ بھی نہیں۔
            </p>
            <p style="font-size:12px; line-height:1.7; color:#64748b; margin:8px 0 0;">
                If you did not try to log in, tell the manager immediately.
                <br>اگر آپ نے لاگ اِن کی کوشش نہیں کی تو فوری مینیجر کو بتائیں۔
            </p>
        </div>

        <div style="background:#f8fafc; padding:14px; text-align:center; font-size:11px; color:#94a3b8;">
            {{ $stationName }} — Vital ERP &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
