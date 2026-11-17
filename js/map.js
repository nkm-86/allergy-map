(() => {
    'use strict';
    const cards = [...document.querySelectorAll('.map-restaurant-card')];
    const canvas = document.getElementById('restaurant-map');
    const selected = document.getElementById('map-selected');
    // Approximate address-area positions, not verified entrances. Never guess a position for unknown addresses.
    const areas = [
        ['茶屋町2-16', 34.7064, 135.4997],
        ['堂山町1-5', 34.7029, 135.5033],
        ['角田町9-17', 34.7041, 135.4987],
        ['梅田3', 34.7039, 135.4955],
        ['梅田1-13-13', 34.7011, 135.4974],
        ['中崎西1', 34.7066, 135.5053],
        ['角田町8-7', 34.7026, 135.4989],
        ['芝田1-1-3', 34.7053, 135.4982],
    ];
    let map;
    let layer;
    const markers = new Map();
    const motion = !window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function position(card) {
        const address = card.dataset.address || card.querySelector('.card-address')?.textContent || '';
        const normalized = address.replace(/丁目/g, '-').replace(/[－ー]/g, '-').replace(/\s/g, '');
        return areas.find(([key]) => normalized.includes(key))?.slice(1);
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
            if (!hasTiles) document.querySelector('.map-location-note').textContent = '地図背景を読み込めません。ネットワーク接続をご確認ください。ピンは住所付近の目安です。';
        });
        layer = L.layerGroup().addTo(map);
        refreshMarkers();
        new ResizeObserver(() => map.invalidateSize()).observe(canvas);
    } else {
        canvas.querySelector('.map-fallback').textContent = '地図を読み込めませんでした。ネットワーク接続をご確認ください。店舗情報は一覧からご覧いただけます。';
        document.getElementById('show-all').disabled = true;
    }
    document.getElementById('show-all').addEventListener('click', fitAll);
    // The HTML preview filters locally; the PHP page keeps its existing database search.
    const previewForm = document.querySelector('.preview-search');
    if (previewForm) {
        previewForm.addEventListener('submit', event => {
            event.preventDefault();
            const keyword = previewForm.querySelector('input[type=text]').value.trim().toLocaleLowerCase();
            const allergies = [...previewForm.querySelectorAll('input:checked')].map(input => input.parentElement.textContent.trim());
            cards.forEach(card => {
                const tags = [...card.querySelectorAll('.map-allergy-info span')].map(tag => tag.textContent.trim());
                card.hidden = !card.textContent.toLocaleLowerCase().includes(keyword) || !allergies.every(allergy => tags.some(tag => tag.startsWith(allergy + '不使用') || tag.startsWith(allergy + '抜き')));
                card.classList.remove('is-selected');
                card.querySelector('.show-on-map').setAttribute('aria-pressed', 'false');
            });
            const count = cards.filter(card => !card.hidden).length;
            document.querySelector('.result-count').textContent = `${count}件の店舗が見つかりました`;
            document.querySelector('.map-empty').hidden = count > 0;
            selected.textContent = '店舗の「地図で見る」か、地図の番号を選んでください。';
            refreshMarkers();
        });
    }
})();
