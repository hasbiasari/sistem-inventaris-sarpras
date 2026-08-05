<h5 class="fw-bold mb-1 text-danger">Delete Account</h5>
<p class="text-muted small mb-3">
    Once your account is deleted, all of its resources and data will be permanently deleted.
    Before deleting your account, please download any data or information that you wish to retain.
</p>

<button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalHapusAkun">
    <i class="bi bi-trash-fill"></i> Delete Account
</button>

<div class="modal fade" id="modalHapusAkun" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')
                <div class="modal-header">
                    <h5 class="modal-title">Are you sure you want to delete your account?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">
                        Once your account is deleted, all of its resources and data will be permanently deleted.
                        Please enter your password to confirm you would like to permanently delete your account.
                    </p>
                    <label for="delete_password" class="form-label visually-hidden">Password</label>
                    <input id="delete_password" type="password" name="password" placeholder="Password"
                           class="form-control @error('password', 'userDeletion') is-invalid @enderror">
                    @error('password', 'userDeletion') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if ($errors->userDeletion->isNotEmpty())
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('modalHapusAkun')).show();
        });
    </script>
@endif
