(() => {
    'use strict';
    const cards = [...document.querySelectorAll('.map-restaurant-card')];
    const canvas = document.getElementById('restaurant-map');
    const selected = document.getElementById('map-selected');
    let map;
    let layer;
    const markers = new Map();
    const motion = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function position(card) {
        if (!card.dataset.latitude || !card.dataset.longitude) return null;
        const lat = Number(card.dataset.latitude), lng = Number(card.dataset.longitude);
        return Number.isFinite(lat) && Number.isFinite(lng) && Math.abs(lat)<=90 && Math.abs(lng)<=180 ? [lat,lng] : null;
    }
    function selectCard(card, fromMarker = false) {
        cards.forEach(item => {
            const active = item === card;
            item.classList.toggle('is-selected', active);
            item.querySelector('.show-on-map')?.setAttribute('aria-pressed', String(active));
            markers.get(item)?.getElement()?.classList.toggle('is-active', active);
        });
        selected.replaceChildren();
        const title = document.createElement('strong');
        title.textContent = card.querySelector('h4').textContent;
        const address = document.createElement('p');
        address.textContent = card.dataset.address || card.querySelector('.card-address')?.textContent;
        const link = card.querySelector('.card-actions a').cloneNode(true);
        selected.append(title, address, link);
        const marker = markers.get(card);
        if (marker) {
            map.setView(marker.getLatLng(), Math.max(map.getZoom(), 16), { animate: motion });
            marker.openTooltip();
        } else if (map) {
            const note = document.createElement('p');
            note.textContent = 'この店舗の地図位置は未登録です。店舗詳細をご確認ください。';
            selected.append(note);
        }
        if (fromMarker) card.scrollIntoView({ behavior: motion ? 'smooth' : 'auto', block: 'nearest' });
        else if (window.innerWidth <= 640) canvas.scrollIntoView({ behavior: motion ? 'smooth' : 'auto', block: 'center' });
    }
    cards.forEach(card => {
        const button = card.querySelector('.show-on-map');
        button.setAttribute('aria-pressed', 'false');
        button.addEventListener('click', () => selectCard(card));
    });
    function fitAll() {
        if (!map) return;
        const points = [...markers.values()].map(marker => marker.getLatLng());
        if (points.length) map.fitBounds(L.latLngBounds(points), { padding: [45, 45], maxZoom: 16, animate: motion });
        else map.setView([34.704, 135.5], 15);
    }
    function refreshMarkers() {
        if (!map) return;
        layer.clearLayers();
        markers.clear();
        cards.filter(card => !card.hidden).forEach(card => {
            const point = position(card);
            if (!point) return;
            const number = card.querySelector('.card-number').textContent.trim();
            const name = card.querySelector('h4').textContent.trim();
            const icon = L.divIcon({ className: 'restaurant-marker', html: `<span>${Number(number)}</span>`, iconSize: [30, 30], iconAnchor: [15, 15] });
            const marker = L.marker(point, { icon, title: `${number}. ${name}`, alt: name, keyboard: true }).addTo(layer);
            const label = document.createElement('span');
            label.textContent = name;
            marker.bindTooltip(label, { direction: 'top', offset: [0, -14] });
            marker.on('click', () => selectCard(card, true));
            markers.set(card, marker);
        });
        fitAll();
    }
    if (window.L) {
        canvas.replaceChildren();
        map = L.map(canvas, { scrollWheelZoom: false, zoomControl: false }).setView([34.704, 135.5], 15);
        L.control.zoom({ position: 'bottomright' }).addTo(map);
        const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);
        let hasTiles = false;
        tiles.on('tileload', () => { hasTiles = true; });
        tiles.on('tileerror', () => {
            if (!hasTiles) document.querySelector('.map-location-note').textContent = '地図背景を読み込めません。ネットワーク接続をご確認ください。座標登録済みの店舗のみ表示します。';
        });
        layer = L.layerGroup().addTo(map);
        refreshMarkers();
        new ResizeObserver(() => map.invalidateSize()).observe(canvas);
    } else {
        canvas.querySelector('.map-fallback').textContent = '地図を読み込めませんでした。ネットワーク接続をご確認ください。店舗情報は一覧からご覧いただけます。';
        document.getElementById('show-all').disabled = true;
    }
    document.getElementById('show-all').addEventListener('click', fitAll);
})();
