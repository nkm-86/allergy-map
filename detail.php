<?php
require_once __DIR__ . '/db_connect.php';

// アレルギー品目ごとのアイコン定義
$ALLERGY_ICONS = [
    'egg'       => '🥚',
    'milk'      => '🥛',
    'wheat'     => '🌾',
    'peanut'    => '🥜',
    'shrimp'    => '🦐',
    'crab'      => '🦀',
    'buckwheat' => '🍜',
    'walnut'    => '🌰',
];

// URLパラメータから店舗IDを取得（未指定の場合はID=1をデフォルト表示）
$id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

$stmt = $pdo->prepare('SELECT * FROM restaurants WHERE id = :id');
$stmt->execute([':id' => $id]);
$restaurant = $stmt->fetch();

// 関連店舗（同じジャンルを優先し、現在の店舗以外を最大3件取得）
$related_restaurants = [];
if ($restaurant) {
    $rel_stmt = $pdo->prepare(
        'SELECT * FROM restaurants
         WHERE id != :id
         ORDER BY (genre_code = :genre_code) DESC, id ASC
         LIMIT 3'
    );
    $rel_stmt->execute([
        ':id'         => $restaurant['id'],
        ':genre_code' => $restaurant['genre_code'],
    ]);
    $related_restaurants = $rel_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $restaurant ? htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') . ' | ' : '店舗詳細 | ' ?>Allergy Map</title>

    <link rel="stylesheet" href="css/common.css">
    <link rel="stylesheet" href="css/detail.css">
</head>

<body>

    <!-- =========================
         ヘッダー
    ========================== -->
    <header class="header">
        <div class="header-inner">

            <h1 class="logo">
                <a href="index.php">Allergy Map</a>
            </h1>

            <nav class="navigation">
                <a href="index.php">HOME</a>
                <a href="index.php#search">検索</a>
                <a href="index.php#notice">店舗一覧</a>
                <a href="map.php">マップ</a>
            </nav>

        </div>
    </header>


    <main>

        <?php if (!$restaurant): ?>
            <!-- 該当店舗が存在しない場合 -->
            <section class="detail-container">
                <div class="detail-not-found">
                    <h2>指定された店舗情報が見つかりませんでした。</h2>
                    <p>URLが正しいかご確認いただくか、店舗一覧からお探しください。</p>
                    <a href="index.php#notice" class="btn-primary">← 店舗一覧へ戻る</a>
                </div>
            </section>
        <?php else: ?>
            <?php
            $tags = officialTags($pdo, (int)$restaurant['id']);
            $sources_stmt=$pdo->prepare('SELECT * FROM allergy_sources WHERE restaurant_id=? ORDER BY id');
            $sources_stmt->execute([$restaurant['id']]);
            $sources=$sources_stmt->fetchAll();
            $supported_codes = publishedCodes($pdo, (int)$restaurant['id']);
            $coverage = allergenCoverage($pdo, (int)$restaurant['id']);
            $map_query = rawurlencode($restaurant['name'] . ' ' . $restaurant['address']);
            ?>

            <!-- パンくずリスト -->
            <nav class="breadcrumb" aria-label="パンくずリスト">
                <ol>
                    <li><a href="index.php">HOME</a></li>
                    <li><a href="index.php#notice">店舗一覧</a></li>
                    <li aria-current="page"><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?></li>
                </ol>
            </nav>

            <div class="detail-container">

                <!-- =========================
                     店舗メイン情報カード
                ========================== -->
                <section class="detail-hero-card">

                    <div class="detail-hero-image">
                        <img
                            src="<?= htmlspecialchars($restaurant['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?>のイメージ画像"
                        >
                    </div>

                    <div class="detail-hero-content">

                        <div class="detail-badges">
                            <span class="genre-badge">
                                <?= htmlspecialchars($restaurant['genre'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if (isset($GENRE_OPTIONS[$restaurant['genre_code']])): ?>
                                <a href="index.php?genre=<?= urlencode($restaurant['genre_code']) ?>#notice" class="genre-category-link">
                                    #<?= htmlspecialchars($GENRE_OPTIONS[$restaurant['genre_code']], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            <?php endif; ?>
                        </div>

                        <h2 class="detail-title"><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?></h2>

                        <p class="detail-address">
                            <span>📍</span> <?= htmlspecialchars($restaurant['address'], ENT_QUOTES, 'UTF-8') ?>
                        </p>

                        <div class="detail-tag-section">
                            <p class="detail-tag-heading">公式情報の確認状況</p>
                            <div class="detail-tags">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="tag-pill"><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="detail-source-box">
                            <p class="detail-source">既存URLは内容未確認として移行しています。下記の情報源をご確認ください。</p>

                        </div>

                    </div>

                </section>


                <!-- =========================
                     特定原材料8品目の対応状況
                ========================== -->
                <section class="detail-panel">

                    <div class="panel-header">
                        <h3>特定原材料8品目の情報</h3>
                        <p>公式情報で各品目の記載を確認できているかを表示します。不使用・安全性を示すものではありません。</p>
                    </div>

                    <div class="allergy-matrix">
                        <?php foreach ($ALLERGY_OPTIONS as $code => $label): ?>
                            <?php
                            $is_supported = in_array($code, $supported_codes, true);
                            $icon = isset($ALLERGY_ICONS[$code]) ? $ALLERGY_ICONS[$code] : '🍽️';
                            ?>
                            <div class="allergy-item <?= $is_supported ? 'is-supported' : 'is-unconfirmed' ?>">
                                <span class="allergy-icon"><?= $icon ?></span>
                                <span class="allergy-name"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($is_supported): ?>
                                    <span class="allergy-status status-ok">公式資料に記載確認済み</span>
                                <?php else: ?>
                                    <span class="allergy-status status-check"><?= ($coverage[$code] ?? 'not_confirmed') === 'not_applicable' ? '資料の対象外' : '記載未確認' ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="allergy-note-box">
                        <p>
                            ※公式資料への記載確認は、アレルゲン不使用を意味しません。未確認の情報も不使用として扱いません。利用前に公式資料と店舗へご確認ください。
                        </p>
                    </div>

                </section>


                <!-- =========================
                     店舗基本情報・アクセスマップ
                ========================== -->
                <section class="detail-panel">

                    <div class="panel-header">
                        <h3>店舗基本情報・アクセス</h3>
                    </div>

                    <div class="info-map-grid">

                        <dl class="detail-info-table">
                            <div>
                                <dt>店舗名</dt>
                                <dd><?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?></dd>
                            </div>
                            <div>
                                <dt>ジャンル</dt>
                                <dd><?= htmlspecialchars($restaurant['genre'], ENT_QUOTES, 'UTF-8') ?></dd>
                            </div>
                            <div>
                                <dt>住所</dt>
                                <dd>
                                    <?= htmlspecialchars($restaurant['address'], ENT_QUOTES, 'UTF-8') ?>
                                    <div class="external-map-link-wrap">
                                        <a
                                            href="https://www.google.com/maps/search/?api=1&query=<?= $map_query ?>"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="external-map-link"
                                        >Googleマップで開く ↗</a>
                                    </div>
                                </dd>
                            </div>
                            <div>
                                <dt>アレルギー表示</dt>
                                <dd><?= htmlspecialchars(implode(' / ', $tags), ENT_QUOTES, 'UTF-8') ?></dd>
                            </div>
                            <div>
                                <dt>情報提供元</dt>
                                <dd>各情報源の確認状態を参照</dd>
                            </div>

                        </dl>

                        <div class="detail-map-frame">
                            <iframe
                                title="<?= htmlspecialchars($restaurant['name'], ENT_QUOTES, 'UTF-8') ?>の地図"
                                src="https://maps.google.com/maps?q=<?= $map_query ?>&t=m&z=16&output=embed&iwloc=near"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                            ></iframe>
                        </div>

                    </div>

                </section>


                <!-- =========================
                     関連するお店・近くのお店
                ========================== -->
                <?php if (!empty($related_restaurants)): ?>
                    <section class="related-section">

                        <div class="related-header">
                            <h3>他の店舗も見る</h3>
                            <a href="index.php#notice">すべての店舗を見る →</a>
                        </div>

                        <div class="related-list">
                            <?php foreach ($related_restaurants as $rel): ?>
                                <?php
                                $rel_tags = officialTags($pdo, (int)$rel['id']);
                                ?>
                                <article class="related-card">
                                    <div class="related-image">
                                        <img
                                            src="<?= htmlspecialchars($rel['image_path'], ENT_QUOTES, 'UTF-8') ?>"
                                            alt="<?= htmlspecialchars($rel['name'], ENT_QUOTES, 'UTF-8') ?>"
                                        >
                                    </div>
                                    <div class="related-info">
                                        <p class="related-genre"><?= htmlspecialchars($rel['genre'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <h4><?= htmlspecialchars($rel['name'], ENT_QUOTES, 'UTF-8') ?></h4>
                                        <div class="related-tags">
                                            <?php foreach ($rel_tags as $r_tag): ?>
                                                <span><?= htmlspecialchars($r_tag, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                        <a href="detail.php?id=<?= (int)$rel['id'] ?>" class="related-link">詳細を見る</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>

                    </section>
                <?php endif; ?>


                <section class="detail-panel"><div class="panel-header"><h3>公式情報源と確認状態</h3></div>
<?php if (!$sources): ?><p>情報源は未登録です。</p><?php endif; ?>
<?php foreach ($sources as $source): ?>
<div class="allergy-note-box"><strong><?= htmlspecialchars($source['title'], ENT_QUOTES, 'UTF-8') ?></strong>
<p>確認状態：<?= ['verified'=>'確認済み','pending'=>'未確認','invalid'=>'無効'][$source['verification_status']] ?? '未確認' ?> ／ 最終確認：<?= htmlspecialchars($source['checked_at'] ?? '未確認', ENT_QUOTES, 'UTF-8') ?></p>
<?php if (filter_var($source['source_url'], FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($source['source_url'], PHP_URL_SCHEME)), ['http','https'], true)): ?><a class="source-url-link" href="<?= htmlspecialchars($source['source_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($source['source_url'], ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
</div><?php endforeach; ?></section>
<!-- 戻るボタン -->
                <div class="detail-bottom-nav">
                    <a href="index.php#notice" class="btn-secondary">← 店舗一覧に戻る</a>
                    <a href="map.php" class="btn-outline">📍 マップから探す</a>
                </div>

            </div>

        <?php endif; ?>


        <!-- =========================
             注意事項
        ========================== -->
        <section class="notice-section">

            <h2>ご利用上の注意</h2>

            <p>
                掲載されているアレルギー情報は、
                すべてのアレルギー反応を防ぐことを保証するものではありません。
            </p>

            <p>
                調理器具や設備の共用などにより、
                アレルゲンが混入する可能性があります。
            </p>

            <p>
                ご利用前に必ず店舗へ直接ご確認ください。
            </p>

        </section>

    </main>


    <!-- =========================
         フッター
    ========================== -->
    <footer class="footer">
        <p>&copy; 2026 Allergy Map. All Rights Reserved.</p>
    </footer>

    <script src="js/common.js"></script>
</body>
</html>
