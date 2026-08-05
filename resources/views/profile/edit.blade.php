<x-app-layout>
    <x-slot name="header">
        <h2 class="fw-bold fs-4 mb-0">Profile</h2>
    </x-slot>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    @include('profile.partials.update-password-form')
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card border-danger-subtle">
                <div class="card-body">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>

    <script>
        // checkbox "perlihatkan semua password" - toggle 3 field sekaligus
        const checkboxShowAll = document.getElementById('show-all-password');
        if (checkboxShowAll) {
            checkboxShowAll.addEventListener('change', function () {
                const tipe = this.checked ? 'text' : 'password';
                document.querySelectorAll('.toggle-password-group').forEach(function (input) {
                    input.type = tipe;
                });
            });
        }

        // ilangin pesan "Tersimpan." otomatis abis beberapa detik
        document.querySelectorAll('.saved-flash').forEach(function (el) {
            setTimeout(function () {
                el.style.transition = 'opacity .4s ease';
                el.style.opacity = '0';
                setTimeout(function () { el.remove(); }, 400);
            }, 2000);
        });
    </script>
</x-app-layout>
