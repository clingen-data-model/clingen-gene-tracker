<h2>Welcome to the {{config('app.title')}} system.</h2>

<p>An account has been created for you using this email address.</p>

<p>
  To get started you'll need to <a href="{{ route('password.request') }}">Set your password</a>.
</p>
<p>Here's how:</p>
<ul>
  <li>Visit <a href="{{ route('password.request') }}">{{ route('password.request') }}</a></li>
  <li>Enter your email address.</li>
  <li>Check your email (don't forget your spam folder, just in case), and follow the link.</li>
  <li>Enter and confirm your password</li>
</ul>