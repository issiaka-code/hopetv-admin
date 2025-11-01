<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Reverb - InfoBulles en temps réel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col items-center py-10">
    <div class="w-full max-w-3xl bg-white p-6 rounded-2xl shadow">
        <h1 class="text-2xl font-bold mb-4 text-center">🛰️ Test Temps Réel - InfoBulles</h1>

        <div id="connection" class="text-center text-gray-600 mb-4">Connexion à Reverb...</div>

        <div id="events" class="space-y-2">
            <p class="text-gray-400 italic">Aucun événement reçu pour l’instant…</p>
        </div>
    </div>

  <!-- Pusher/echo pour Reverb -->
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1/dist/echo.iife.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Initialise Echo
    window.Echo = new Echo({
        broadcaster: 'reverb', // ou 'pusher' si tu utilises Pusher
        key: '{{ config("reverb.apps")[0]["key"] ?? "test" }}', 
        wsHost: window.location.hostname,
        wsPort: 8080,
        forceTLS: false,
        disableStats: true,
    });

    // Écoute le canal info-bulles
    Echo.channel('info-bulles')
        .listen('.info-bulle.changed', (e) => {
            console.log('Événement reçu:', e);

            // Affiche une notification sans recharger
            let actionText = '';
            switch(e.action){
                case 'create': actionText = 'ajoutée'; break;
                case 'update': actionText = 'modifiée'; break;
                case 'delete': actionText = 'supprimée'; break;
                case 'toggle': actionText = 'statut changé'; break;
            }

            Swal.fire({
                icon: 'info',
                title: `Info-bulle ${actionText}`,
                text: `ID: ${e.infoBulleId}`,
                timer: 3000,
                showConfirmButton: false
            });
        });
</script>

</body>
</html>
