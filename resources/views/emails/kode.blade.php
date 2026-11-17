{{-- Email kode OTP (daftar & lupa sandi). Tabel + inline style supaya rapi di Gmail/Outlook. --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $judul }}</title>
</head>
<body style="margin:0;padding:0;background:#f3fbe3;font-family:Figtree,'Segoe UI',Helvetica,Arial,sans-serif;color:#15181c;">
    <span style="display:none;max-height:0;overflow:hidden;opacity:0;">Kode kamu: {{ $kode }}. Berlaku {{ $berlaku }}.</span>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3fbe3;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;">
                    <tr>
                        <td align="center" style="padding-bottom:20px;font-size:26px;font-weight:800;letter-spacing:-0.5px;color:#15181c;">
                            {{-- Logo di-embed (CID) karena Gmail memblokir data-URI dan APP_URL lokal tak bisa dijangkau. Fallback teks saat $message tak ada. --}}
                            @isset($message)
                                <img src="{{ $message->embed(public_path('assets/reloop-logo.png')) }}" width="72" height="72" alt="ReLoop" style="display:block;width:72px;height:72px;border:0;border-radius:18px;">
                            @else
                                Re<span style="color:#5f9200;">Loop</span>
                            @endisset
                        </td>
                    </tr>
                    <tr>
                        <td style="background:#ffffff;border-radius:20px;padding:32px 28px;box-shadow:0 8px 24px -12px rgba(16,40,24,.22);">
                            <div style="height:6px;width:48px;border-radius:3px;background:#9be600;margin-bottom:20px;"></div>
                            <h1 style="margin:0 0 8px;font-size:22px;line-height:1.3;font-weight:700;">{{ $judul }}</h1>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#4a5058;">{{ $pembuka }}</p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="background:#ecfbf1;border:2px dashed #72d99c;border-radius:16px;padding:20px 12px;">
                                        <div style="font-size:12px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:#14753b;margin-bottom:8px;">Kode verifikasi</div>
                                        <div style="font-family:'SFMono-Regular',Menlo,Consolas,monospace;font-size:36px;font-weight:800;letter-spacing:10px;color:#0f3d22;padding-left:10px;">{{ $kode }}</div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:20px 0 0;font-size:14px;line-height:1.6;color:#4a5058;">
                                Kode berlaku <strong style="color:#15181c;">{{ $berlaku }}</strong> dan hanya bisa dipakai sekali. Jangan bagikan kode ini ke siapa pun, termasuk yang mengaku dari ReLoop.
                            </p>
                            <p style="margin:16px 0 0;font-size:13px;line-height:1.6;color:#8a9097;">
                                Tidak merasa meminta kode ini? Abaikan saja email ini, akunmu tetap aman.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 8px 0;font-size:12px;line-height:1.6;color:#6b7a5a;">
                            Jual barang bekas ke tetangga, setor sampah ke pengepul terdekat.<br>
                            &copy; {{ date('Y') }} ReLoop
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
