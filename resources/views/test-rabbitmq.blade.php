<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test RabbitMQ Publisher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Menggunakan font Inter agar terlihat lebih modern */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">

    <div class="bg-white rounded-2xl shadow-xl border border-gray-100 w-full max-w-md p-8 relative overflow-hidden">
        <!-- Dekorasi latar belakang (opsional) -->
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 to-indigo-600"></div>

        <h2 class="text-2xl font-bold text-gray-800 text-center mb-2 mt-2">Pendaftaran Anggota</h2>
        <p class="text-sm text-gray-500 text-center mb-6">Formulir test integrasi RabbitMQ</p>
        
        <!-- Alert Box -->
        <div id="alertBox" class="hidden mb-6 p-4 rounded-lg text-sm font-medium transition-all duration-300"></div>

        <form id="membershipForm" class="space-y-5">
            <div>
                <label for="name" class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" id="name" name="name" required 
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none bg-gray-50 focus:bg-white" 
                       placeholder="Masukkan nama lengkap">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required 
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none bg-gray-50 focus:bg-white" 
                       placeholder="email@contoh.com">
            </div>

            <div>
                <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Nomor Telepon</label>
                <input type="tel" id="phone" name="phone" required 
                       class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none bg-gray-50 focus:bg-white" 
                       placeholder="08123456789">
            </div>

            <div>
                <label for="membership_type" class="block text-sm font-semibold text-gray-700 mb-1">Tipe Keanggotaan</label>
                <select id="membership_type" name="membership_type" required 
                        class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all outline-none bg-gray-50 focus:bg-white appearance-none cursor-pointer">
                    <option value="" disabled selected>Pilih tipe keanggotaan...</option>
                    <option value="perunggu">Perunggu</option>
                    <option value="perak">Perak</option>
                    <option value="emas">Emas</option>
                    <option value="platina">Platina</option>
                </select>
            </div>

            <button type="submit" id="submitBtn" 
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 focus:ring-4 focus:ring-indigo-300 mt-6 flex justify-center items-center">
                <span>Daftarkan Anggota</span>
            </button>
        </form>
    </div>

    <script>
        document.getElementById('membershipForm').addEventListener('submit', async function(e) {
            // Mencegah reload halaman
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const alertBox = document.getElementById('alertBox');
            
            // Mengambil data dari input form
            const data = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                membership_type: document.getElementById('membership_type').value,
            };

            // Setup loading state pada tombol dan sembunyikan alert jika ada
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memproses...
            `;
            submitBtn.classList.add('opacity-80', 'cursor-not-allowed');
            
            alertBox.classList.add('hidden');
            alertBox.classList.remove('bg-green-100', 'text-green-800', 'border-green-300', 'bg-red-100', 'text-red-800', 'border-red-300');

            try {
                // Proses mengirim ke endpoint backend
                const response = await fetch('/api/v1/memberships', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-IAE-KEY': 'isi_dengan_api_key_kelompok_saya' // <-- JANGAN LUPA GANTI INI NANTI
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.status === 201 || response.ok) {
                    // Berhasil
                    alertBox.textContent = 'Sukses! Anggota didaftarkan dan log terkirim ke RabbitMQ.';
                    alertBox.classList.add('bg-green-100', 'text-green-800', 'border', 'border-green-300');
                    alertBox.classList.remove('hidden');
                    
                    this.reset(); // Mengosongkan form
                } else {
                    // Gagal dengan error dari response (misal 400, 401)
                    const errorMsg = result.message || 'Terjadi kesalahan pada validasi atau server.';
                    alertBox.textContent = 'Error: ' + errorMsg;
                    alertBox.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-300');
                    alertBox.classList.remove('hidden');
                }
            } catch (error) {
                // Gagal koneksi atau server down
                alertBox.textContent = 'Error koneksi: ' + error.message;
                alertBox.classList.add('bg-red-100', 'text-red-800', 'border', 'border-red-300');
                alertBox.classList.remove('hidden');
            } finally {
                // Mengembalikan keadaan tombol seperti semula
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Daftarkan Anggota';
                submitBtn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        });
    </script>
</body>
</html>
