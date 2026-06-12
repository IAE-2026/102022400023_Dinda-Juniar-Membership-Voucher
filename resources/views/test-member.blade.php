<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Coba Pendaftaran Member API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center py-10 px-4">

    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-lg p-8 relative overflow-hidden">
        <!-- Enterprise Header Decor -->
        <div class="absolute top-0 left-0 w-full h-2 bg-slate-800"></div>

        <h2 class="text-2xl font-bold text-slate-800 text-center mt-2 mb-1">Pendaftaran Keanggotaan</h2>
        <p class="text-sm text-slate-500 text-center mb-8">Uji coba interaktif endpoint <code class="bg-slate-100 px-1.5 py-0.5 rounded text-slate-700 font-mono text-xs border border-slate-200">/api/v1/memberships</code></p>
        
        <!-- Alert Box -->
        <div id="alertBox" class="hidden mb-6 p-4 rounded-lg text-sm font-medium transition-all duration-300"></div>

        <form id="testForm" class="space-y-5">
            <!-- Token Section -->
            <div class="p-5 bg-slate-50 rounded-xl border border-slate-200">
                <label for="token" class="block text-sm font-semibold text-slate-800 mb-1">Token M2M / API Key Dosen</label>
                <input type="password" id="token" name="token" required 
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all outline-none bg-white font-mono text-sm" 
                       placeholder="Masukkan Bearer Token...">
                <p class="text-xs text-slate-500 mt-2 flex items-center">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Wajib diisi untuk proses otentikasi API.
                </p>
            </div>

            <hr class="border-slate-100">

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-1">Nama Lengkap</label>
                <input type="text" id="name" name="name" required 
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all outline-none bg-slate-50 focus:bg-white" 
                       placeholder="Contoh: Budi Santoso">
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-1">Email</label>
                <input type="email" id="email" name="email" required 
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all outline-none bg-slate-50 focus:bg-white" 
                       placeholder="budi@example.com">
            </div>

            <div>
                <label for="phone" class="block text-sm font-semibold text-slate-700 mb-1">No. Handphone</label>
                <input type="text" id="phone" name="phone" required 
                       class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all outline-none bg-slate-50 focus:bg-white" 
                       placeholder="081234567890">
            </div>

            <div>
                <label for="membership_type" class="block text-sm font-semibold text-slate-700 mb-1">Tipe Keanggotaan</label>
                <div class="relative">
                    <select id="membership_type" name="membership_type" required 
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all outline-none bg-slate-50 focus:bg-white appearance-none cursor-pointer">
                        <option value="" disabled selected>Pilih tipe...</option>
                        <option value="perunggu">Perunggu</option>
                        <option value="perak">Perak</option>
                        <option value="emas">Emas</option>
                        <option value="platina">Platina</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </div>
                </div>
            </div>

            <button type="submit" id="submitBtn" 
                    class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-3.5 px-4 rounded-lg shadow hover:shadow-lg transition-all duration-300 focus:ring-4 focus:ring-slate-200 mt-6 flex justify-center items-center">
                <span>Daftarkan Member</span>
            </button>
        </form>
    </div>

    <script>
        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const alertBox = document.getElementById('alertBox');
            
            // Mengambil data input
            const tokenValue = document.getElementById('token').value;
            const data = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                membership_type: document.getElementById('membership_type').value,
            };

            // Loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = `
                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            `;
            submitBtn.classList.add('opacity-80', 'cursor-not-allowed');
            
            alertBox.classList.add('hidden');
            alertBox.classList.remove('bg-emerald-50', 'text-emerald-800', 'border-emerald-200', 'bg-rose-50', 'text-rose-800', 'border-rose-200');

            try {
                const response = await fetch('/api/v1/memberships', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': 'Bearer ' + tokenValue
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.status === 201 || response.ok) {
                    alertBox.textContent = 'Member berhasil didaftarkan! Cek papan pengumuman RabbitMQ dosen.';
                    alertBox.classList.add('bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200');
                    alertBox.classList.remove('hidden');
                    
                    // Reset fields except token
                    document.getElementById('name').value = '';
                    document.getElementById('email').value = '';
                    document.getElementById('phone').value = '';
                    document.getElementById('membership_type').value = '';
                } else {
                    const errorMsg = result.message || 'Terjadi kesalahan pada server.';
                    let validationErrors = '';
                    if (result.errors) {
                        validationErrors = ' - ' + JSON.stringify(result.errors);
                    }
                    alertBox.textContent = 'Error: ' + errorMsg + validationErrors;
                    alertBox.classList.add('bg-rose-50', 'text-rose-800', 'border', 'border-rose-200');
                    alertBox.classList.remove('hidden');
                }
            } catch (error) {
                alertBox.textContent = 'Error koneksi: ' + error.message;
                alertBox.classList.add('bg-rose-50', 'text-rose-800', 'border', 'border-rose-200');
                alertBox.classList.remove('hidden');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Daftarkan Member';
                submitBtn.classList.remove('opacity-80', 'cursor-not-allowed');
            }
        });
    </script>
</body>
</html>
