<?php
function requestText(array $input, string $key): string { return isset($input[$key]) && is_string($input[$key]) ? trim($input[$key]) : ''; }
function searchRestaurants(PDO $pdo, array $input): array {
    $sql = 'SELECT r.* FROM restaurants r WHERE 1=1'; $params = [];
    $keyword = requestText($input, 'keyword');
    if ($keyword !== '') { $sql .= ' AND (r.name LIKE ? OR r.address LIKE ?)'; $params[] = "%$keyword%"; $params[] = "%$keyword%"; }
    foreach (['genre'=>'genre_code','prefecture'=>'prefecture_code'] as $key=>$column) {
        if (($value=requestText($input,$key)) !== '') { $sql .= " AND r.$column=?"; $params[]=$value; }
    }
    if (requestText($input,'official') === '1') $sql .= " AND EXISTS (SELECT 1 FROM allergy_sources s WHERE s.restaurant_id=r.id AND s.verification_status='verified' AND s.checked_at IS NOT NULL AND s.source_url<>'')";
    $allergies = $input['allergies'] ?? [];
    if (is_array($allergies)) foreach (array_unique(array_filter($allergies,'is_string')) as $code) {
        $sql .= " AND EXISTS (SELECT 1 FROM allergy_sources s JOIN source_allergens sa ON sa.source_id=s.id WHERE s.restaurant_id=r.id AND s.verification_status='verified' AND s.checked_at IS NOT NULL AND sa.allergen_code=? AND sa.coverage_status='published' AND sa.checked_at IS NOT NULL)";
        $params[]=$code;
    }
    $stmt=$pdo->prepare($sql.' ORDER BY r.id'); $stmt->execute($params); return $stmt->fetchAll();
}
function publishedCodes(PDO $pdo, int $id): array {
    $q=$pdo->prepare("SELECT DISTINCT sa.allergen_code FROM allergy_sources s JOIN source_allergens sa ON s.id=sa.source_id WHERE s.restaurant_id=? AND s.verification_status='verified' AND s.checked_at IS NOT NULL AND sa.coverage_status='published' AND sa.checked_at IS NOT NULL");
    $q->execute([$id]); return $q->fetchAll(PDO::FETCH_COLUMN);
}
function officialTags(PDO $pdo, int $id): array { return publishedCodes($pdo,$id) ? ['確認済みの公式アレルギー情報あり'] : ['公式アレルギー情報は未確認']; }
function allergenCoverage(PDO $pdo, int $id): array {
    $q=$pdo->prepare("SELECT sa.allergen_code,sa.coverage_status FROM allergy_sources s JOIN source_allergens sa ON s.id=sa.source_id WHERE s.restaurant_id=? AND s.verification_status='verified' AND s.checked_at IS NOT NULL AND sa.checked_at IS NOT NULL");
    $q->execute([$id]); $states=[];
    foreach ($q->fetchAll() as $row) {
        $code=$row['allergen_code']; $status=$row['coverage_status'];
        if ($status==='published' || (!isset($states[$code]) && $status==='not_applicable')) $states[$code]=$status;
    }
    return $states;
}
