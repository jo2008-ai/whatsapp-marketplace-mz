@component('mail::message')

Olá {{ $user->name }},

Recebemos um pedido para repor a tua password.

@component('mail::button', ['url' => url("/repor-password?token={$token}&email={$user->email}")])
Repor Password
@endcomponent

Se não pediste esta alteração, podes ignorar este email.

Atenções,<br>
{{ config('app.name') }}
@endcomponent
