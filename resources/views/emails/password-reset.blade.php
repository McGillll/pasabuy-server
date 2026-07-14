<x-mail::message>
# Reset Your Password

Hi {{ $user->first_name }},

We received a request to reset the password for your Pasabuy account. This link will expire in 60 minutes.

<x-mail::button :url="$resetUrl">
Reset Password
</x-mail::button>

If you didn't request a password reset, you can safely ignore this email — your password will remain unchanged.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
