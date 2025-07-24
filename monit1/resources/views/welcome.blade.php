<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard Cuaca</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body class="bg-gray-900 text-white min-h-screen font-sans flex flex-col items-center">
    <header class="w-full bg-gray-800 shadow-md flex items-center justify-between px-6 py-4">
        <div class="flex items-center">
            <h1 class="text-3xl font-bold flex items-center gap-x-2 mb-2">
                <i class="ph ph-cloud text-4xl text-blue-400"></i> Dashboard Cuaca
            </h1>
        </div>
        <div id="clock" class="text-lg font-mono"></div>
    </header>

    <div class="flex gap-2 w-full max-w-lg mt-6 px-6">
        <input id="city" type="text" value="Jakarta" class="flex-1 bg-gray-800 text-white px-4 py-2 rounded-lg"
            placeholder="Masukkan nama kota" />
        <button onclick="getWeather()"
            class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded-lg font-semibold">Cari</button>
    </div>

    <div id="loading" class="mt-6 hidden">
        <div class="flex flex-col items-center">
            <div class="w-10 h-10 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
            <p class="mt-2 text-sm text-gray-300">Mengambil data cuaca...</p>
        </div>
    </div>

    <div class="w-full max-w-6xl space-y-6 px-6 mt-6">
        <div id="weather-cards" class="grid grid-cols-2 md:grid-cols-3 gap-4"></div>
        <div class="bg-gray-800 p-4 rounded-xl">
            <h2 class="text-lg font-semibold mb-2 text-center">Suhu Per Jam</h2>
            <canvas id="tempChart" height="120"></canvas>
        </div>
    </div>

    <script>
        const weatherEl = document.getElementById('weather-cards');
        const tempChartEl = document.getElementById('tempChart');
        const loadingEl = document.getElementById('loading');
        let chartInstance = null;

        async function getWeather() {
            const city = document.getElementById('city').value;
            loadingEl.classList.remove('hidden');
            weatherEl.innerHTML = "";

            try {
                const geoRes = await fetch(`https://geocoding-api.open-meteo.com/v1/search?name=${city}&count=1`);
                const geoData = await geoRes.json();

                if (!geoData.results || geoData.results.length === 0) {
                    alert("Kota tidak ditemukan");
                    loadingEl.classList.add('hidden');
                    return;
                }

                const {
                    latitude,
                    longitude
                } = geoData.results[0];

                const weatherRes = await fetch(
                    `https://api.open-meteo.com/v1/forecast?latitude=${latitude}&longitude=${longitude}&current=temperature_2m,relative_humidity_2m,pressure_msl,wind_speed_10m,wind_direction_10m,weather_code,uv_index,visibility&hourly=temperature_2m&timezone=auto`
                );
                const weatherData = await weatherRes.json();
                const data = weatherData.current;

                const weatherCodeMap = {
                    0: "Cerah",
                    1: "Cerah Sedikit",
                    2: "Berawan",
                    3: "Mendung",
                    45: "Berkabut",
                    48: "Kabut Beku",
                    51: "Gerimis Ringan",
                    61: "Hujan Ringan",
                    63: "Hujan Sedang",
                    65: "Hujan Lebat",
                    71: "Salju Ringan",
                    80: "Hujan Lokal",
                    95: "Badai Petir"
                };

                const cards = [{
                        icon: "ph-thermometer",
                        label: "Suhu",
                        value: `${data.temperature_2m} °C`,
                        color: "text-red-400"
                    },
                    {
                        icon: "ph-drop-half",
                        label: "Kelembaban",
                        value: `${data.relative_humidity_2m} %`,
                        color: "text-blue-400"
                    },
                    {
                        icon: "ph-gauge",
                        label: "Tekanan",
                        value: `${data.pressure_msl} hPa`,
                        color: "text-purple-400"
                    },
                    {
                        icon: "ph-wind",
                        label: "Angin",
                        value: `${data.wind_speed_10m} m/s`,
                        color: "text-cyan-400"
                    },
                    {
                        icon: "ph-compass",
                        label: "Arah Angin",
                        value: `${data.wind_direction_10m}°`,
                        color: "text-yellow-400"
                    },
                    {
                        icon: "ph-cloud-sun",
                        label: "Cuaca",
                        value: weatherCodeMap[data.weather_code] || "Tidak Diketahui",
                        color: "text-orange-400"
                    },
                    {
                        icon: "ph-sun-dim",
                        label: "UV Index",
                        value: `${data.uv_index ?? '-'}`,
                        color: "text-pink-400"
                    },
                    {
                        icon: "ph-eye",
                        label: "Visibilitas",
                        value: `${data.visibility ?? '-'} m`,
                        color: "text-green-400"
                    }
                ];

                weatherEl.innerHTML = cards.map(c => `
          <div class="bg-gray-800 p-4 rounded-xl flex flex-col items-center">
            <i class="ph ${c.icon} text-4xl ${c.color} mb-2"></i>
            <p class="text-xl font-bold">${c.value}</p>
            <p class="text-sm text-gray-300">${c.label}</p>
          </div>
        `).join('');

                const labels = weatherData.hourly.time.slice(0, 24).map(t => new Date(t).getHours() + ":00");
                const temps = weatherData.hourly.temperature_2m.slice(0, 24);

                if (chartInstance) chartInstance.destroy();

                chartInstance = new Chart(tempChartEl, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Suhu (°C)',
                            data: temps,
                            fill: true,
                            backgroundColor: 'rgba(59, 130, 246, 0.2)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            tension: 0.3
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: false
                            }
                        },
                        plugins: {
                            legend: {
                                display: true,
                                labels: {
                                    color: "#fff"
                                }
                            }
                        }
                    }
                });

            } catch (err) {
                alert("Gagal mengambil data cuaca.");
                console.error(err);
            } finally {
                loadingEl.classList.add('hidden');
            }
        }

        function updateClock() {
            const now = new Date();
            const clock = document.getElementById('clock');
            clock.textContent = now.toLocaleTimeString('id-ID');
        }
        setInterval(updateClock, 1000);
        updateClock();
        getWeather();
    </script>
</body>

</html>
