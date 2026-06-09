// Ativa o Service Worker imediatamente assim que é instalado,
// sem forçar o utilizador a recarregar a página para ver as mudanças.
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// Garante que o Service Worker assume o controlo da página atual de imediato.
self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Interpeta os pedidos de rede (fetch) de forma limpa.
// Deixa passar toda a comunicação diretamente para o teu servidor PHP.
self.addEventListener('fetch', (event) => {
    event.respondWith(
        fetch(event.request).catch((err) => {
            console.log("Erro de rede detetado pelo SW:", err);
        })
    );
});