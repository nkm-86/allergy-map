<div class="official-filters">
    <p>選択した品目の記載を、公式資料で確認済みのお店を検索します。不使用・安全性を示す条件ではありません。</p>
    <label>ジャンル <select name="genre"><option value="">すべて</option><?php foreach ($GENRE_OPTIONS as $code=>$label): ?><option value="<?= $code ?>" <?= $selected_genre===$code?'selected':'' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></label>
    <label>都道府県 <select name="prefecture"><option value="">すべて</option><?php foreach ($PREFECTURES as $code=>$label): ?><option value="<?= $code ?>" <?= $selected_prefecture===$code?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select></label>
    <label><input type="checkbox" name="official" value="1" <?= requestText($_GET,'official')==='1'?'checked':'' ?>> 確認済みの公式情報あり</label>
</div>
