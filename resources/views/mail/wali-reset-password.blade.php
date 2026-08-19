@component('mail::message')
# Reset Password Portal Wali

Assalamu'alaikum,

Ada permintaan reset password untuk akun Portal Wali Anda. Kalau ini memang Anda, klik tombol di bawah untuk membuat password baru:

@component('mail::button', ['url' => $resetUrl, 'color' => 'success'])
Buat Password Baru
@endcomponent

Link ini berlaku selama 15 menit. Kalau Anda tidak meminta reset password, abaikan saja email ini — password Anda tidak berubah.

Terima kasih,<br>
{{ config('app.name') }}
@endcomponent
