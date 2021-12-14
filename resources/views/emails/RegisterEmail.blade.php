@component('mail::message')
# Introduction

Thank you,for Registering our APP.

@component('mail::button', ['url' => $url])
Button Text
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
