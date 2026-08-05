<h5 class="fw-bold mb-1">Profile Information</h5>
<p class="text-muted small mb-4">Update your account's profile information and email address.</p>

<form id="send-verification" method="post" action="{{ route('verification.send') }}">
    @csrf
</form>

<form method="post" action="{{ route('profile.update') }}">
    @csrf
    @method('patch')

    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $user->email) }}" required autocomplete="username">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror

        @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="mt-2 small">
                Your email address is unverified.
                <button form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">
                    Click here to re-send the verification email.
                </button>

                @if (session('status') === 'verification-link-sent')
                    <div class="text-success mt-1">A new verification link has been sent to your email address.</div>
                @endif
            </div>
        @endif
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">Save</button>

        @if (session('status') === 'profile-updated')
            <span class="text-muted small saved-flash">Tersimpan.</span>
        @endif
    </div>
</form>
