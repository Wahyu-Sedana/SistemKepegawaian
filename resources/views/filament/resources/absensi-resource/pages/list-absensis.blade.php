<x-filament-panels::page>
    {{ $this->table }}

    @push('scripts')
        <script>
            // Loading indicator
            function showLoading(message = 'Mengambil lokasi GPS...') {
                window.dispatchEvent(new CustomEvent('open-modal', {
                    detail: {
                        id: 'loading-modal',
                    }
                }));
            }

            function hideLoading() {
                window.dispatchEvent(new CustomEvent('close-modal', {
                    detail: {
                        id: 'loading-modal',
                    }
                }));
            }

            // Function untuk Check-in dengan GPS
            function getLocationAndCheckIn() {
                if (!navigator.geolocation) {
                    alert('Browser Anda tidak mendukung GPS. Gunakan browser modern atau aktifkan lokasi.');
                    return;
                }

                // Tampilkan loading
                showLoading('📍 Mengambil lokasi GPS Anda...');

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        console.log('GPS berhasil:', {
                            lat,
                            lon,
                            accuracy
                        });

                        // Kirim ke Livewire
                        @this.call('processCheckIn', lat, lon)
                            .then(() => {
                                hideLoading();
                            });
                    },
                    function(error) {
                        hideLoading();

                        let errorMessage = '';
                        switch (error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage =
                                    '❌ Anda menolak akses lokasi.\n\nSilakan izinkan akses lokasi di browser Anda.';
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = '❌ Informasi lokasi tidak tersedia.\n\nPastikan GPS aktif.';
                                break;
                            case error.TIMEOUT:
                                errorMessage = '❌ Waktu habis saat mengambil lokasi.\n\nCoba lagi.';
                                break;
                            default:
                                errorMessage = '❌ Terjadi kesalahan: ' + error.message;
                        }

                        alert(errorMessage);
                        console.error('GPS Error:', error);
                    }, {
                        enableHighAccuracy: true, // Gunakan GPS akurat
                        timeout: 10000, // Timeout 10 detik
                        maximumAge: 0 // Jangan gunakan cache
                    }
                );

                return false; // Prevent default action
            }

            // Function untuk Check-out dengan GPS
            function getLocationAndCheckOut() {
                if (!navigator.geolocation) {
                    alert('Browser Anda tidak mendukung GPS.');
                    return;
                }

                showLoading('📍 Mengambil lokasi GPS Anda...');

                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        const lat = position.coords.latitude;
                        const lon = position.coords.longitude;

                        console.log('GPS berhasil:', {
                            lat,
                            lon
                        });

                        @this.call('processCheckOut', lat, lon)
                            .then(() => {
                                hideLoading();
                            });
                    },
                    function(error) {
                        hideLoading();
                        alert('❌ Gagal mengambil lokasi GPS: ' + error.message);
                        console.error('GPS Error:', error);
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );

                return false;
            }

            // Auto request permission saat page load (opsional)
            document.addEventListener('DOMContentLoaded', function() {
                if (navigator.permissions && navigator.permissions.query) {
                    navigator.permissions.query({
                        name: 'geolocation'
                    }).then(function(result) {
                        if (result.state === 'prompt') {
                            console.log('GPS permission: Belum diizinkan');
                        } else if (result.state === 'denied') {
                            console.warn('GPS permission: Ditolak');
                        } else {
                            console.log('GPS permission: Diizinkan');
                        }
                    });
                }
            });
        </script>

        <!-- Loading Modal (Opsional - untuk UX lebih baik) -->
        <div x-data="{ show: false }" x-show="show"
            x-on:open-modal.window="if ($event.detail.id === 'loading-modal') show = true"
            x-on:close-modal.window="if ($event.detail.id === 'loading-modal') show = false" style="display: none;"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-sm mx-4 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
                <p class="text-gray-700 dark:text-gray-300">Mengambil lokasi GPS Anda...</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Harap tunggu sebentar</p>
            </div>
        </div>
    @endpush
</x-filament-panels::page>
