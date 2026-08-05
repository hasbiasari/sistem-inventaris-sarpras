<h5 class="fw-bold mb-1">Update Password</h5>
<p class="text-muted small mb-4">Ensure your account is using a long, random password to stay secure.</p>

<form method="post" action="{{ route('password.update') }}">
    @csrf
    @method('put')

    <div class="mb-3">
        <label for="update_password_current_password" class="form-label">Current Password</label>
        <input id="update_password_current_password" name="current_password" type="password"
               class="form-control toggle-password-group @error('current_password', 'updatePassword') is-invalid @enderror"
               autocomplete="current-password">
        @error('current_password', 'updatePassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="update_password_password" class="form-label">New Password</label>
        <input id="update_password_password" name="password" type="password"
               class="form-control toggle-password-group @error('password', 'updatePassword') is-invalid @enderror"
               autocomplete="new-password">
        @error('password', 'updatePassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="mb-3">
        <label for="update_password_password_confirmation" class="form-label">Confirm Password</label>
        <input id="update_password_password_confirmation" name="password_confirmation" type="password"
               class="form-control toggle-password-group @error('password_confirmation', 'updatePassword') is-invalid @enderror"
               autocomplete="new-password">
        @error('password_confirmation', 'updatePassword') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    {{-- 1 tombol buat buka/tutup semua password sekaligus --}}
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" id="show-all-password">
        <label class="form-check-label" for="show-all-password">
            <i class="bi bi-eye"></i> Perlihatkan semua password
        </label>
    </div>

    <div class="d-flex align-items-center gap-3">
        <button type="submit" class="btn btn-primary">Save</button>

        @if (session('status') === 'password-updated')
            <span class="text-muted small saved-flash">Tersimpan.</span>
        @endif
    </div>
</form>
