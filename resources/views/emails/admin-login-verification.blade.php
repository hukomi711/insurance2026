@component('mail::message')
# رمز تأكيد الدخول

تم طلب تسجيل دخول إلى لوحة تحكم **تأمينكم**.

رمز التأكيد الخاص بك:

@component('mail::panel')
<div style="text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px; direction: ltr;">
{{ $code }}
</div>
@endcomponent

- **صالح لمدة:** 5 دقائق
- **عنوان IP:** {{ $ip }}

إذا لم تطلب تسجيل الدخول، تجاهل هذا البريد.

شكراً,<br>
{{ config('app.name') }}
@endcomponent
