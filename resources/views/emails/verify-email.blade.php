<x-mail::message>
# Verify Your Email Address

Hi {{ $user->first_name }},

Please confirm your email address to finish setting up your Pasabuy account. This link will expire in 60 minutes.

<x-mail::button :url="$verificationUrl">
Verify Email
</x-mail::button>

If you didn't create an account with us, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
