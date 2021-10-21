@component('mail::message')
<p style="text-transform: capitalize;">Hi,</p>
<p>Thank you for choosing Kryptova as your preferred processing partner.</p>
<p>You have successfully signed up for a "Merchant Account" with us. Kindly verify your registered e-mail address by clicking on the link below.</p>
<a href="{{ route('user-activate',$token) }}" class="custom-btn">Verify your email</a>
@endcomponent