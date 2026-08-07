<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email -->
        <div>
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}"
                   placeholder="Contoh: hasbi10222175@sttcipasung.ac.id" required autofocus autocomplete="username">
            @error('email')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Password -->
        <label for="password">Kata Sandi</label>
        <div class="input-group">
            <input id="password" type="password" name="password"
                   placeholder="Masukkan kata sandi kamu" required autocomplete="current-password">
            <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Tampilkan kata sandi">
                <svg class="eye-icon eye-open" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none" />
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" fill="none" />
                </svg>
                <svg class="eye-icon eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12Z" stroke="currentColor" stroke-width="2" fill="none" />
                    <line x1="4" y1="4" x2="20" y2="20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                </svg>
            </button>
        </div>
        @error('password')
            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
        @enderror

        <!-- Submit -->
        <button type="submit" class="btn-primary">Masuk</button>

        <div style="display:flex; justify-content:flex-end; margin-top:-8px;">
            <button type="button" class="guest-link-btn" onclick="openForgotModal()">Lupa kata sandi?</button>
        </div>

        <!-- Remember -->
        <label style="display:flex; align-items:center; gap:8px; font-weight:500;">
            <input type="checkbox" name="remember" id="remember">
            Ingat perangkat ini
        </label>
    </form>

    <div class="forgot-modal-overlay" id="forgotModal" aria-hidden="true">
        <div class="forgot-modal" role="dialog" aria-modal="true" aria-labelledby="forgotModalTitle">
            <button type="button" class="forgot-modal-close" onclick="closeForgotModal()" aria-label="Tutup dialog">×</button>
            <h2 id="forgotModalTitle">Lupa kata sandi</h2>
            <p class="forgot-modal-description">Jangan khawatir. Silakan hubungi admin untuk mereset kata sandi Anda.</p>
            <div class="forgot-modal-actions">
                <button type="button" class="btn-primary" onclick="closeForgotModal()">Oke</button>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const button = document.querySelector('.toggle-password');
            const iconOpen = document.querySelector('.eye-open');
            const iconClosed = document.querySelector('.eye-closed');
            const isPassword = input.type === 'password';

            input.type = isPassword ? 'text' : 'password';
            if (button) {
                button.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            }
            if (iconOpen && iconClosed) {
                iconOpen.style.display = isPassword ? 'none' : 'block';
                iconClosed.style.display = isPassword ? 'block' : 'none';
            }
        }

        function openForgotModal() {
            const modal = document.getElementById('forgotModal');
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }

        function closeForgotModal() {
            const modal = document.getElementById('forgotModal');
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }

        document.addEventListener('click', function (event) {
            const modal = document.getElementById('forgotModal');
            if (event.target === modal) {
                closeForgotModal();
            }
        });

        document.addEventListener('DOMContentLoaded', function () {
            // Keep the modal closed by default unless explicitly requested.
        });
    </script>
</x-guest-layout>