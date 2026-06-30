<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);

function fail2(string $message): void
{
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

$seasonId = (int)($input['season_id'] ?? 0);
$stocks = $input['stocks'] ?? [];

if ($seasonId <= 0 || !is_array($stocks)) {
    fail2('リクエストの形式が正しくありません。');
}

$pdo = get_pdo();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO initial_stocks (season_id, product_id, quantity)
        VALUES (:season_id, :product_id, :quantity)
        ON DUPLICATE KEY UPDATE quantity = :quantity2
    ");

    foreach ($stocks as $s) {
        $productId = (int)($s['product_id'] ?? 0);
        $quantity = (int)($s['quantity'] ?? 0);
        if ($productId <= 0) {
            continue;
        }
        $stmt->execute([
            'season_id' => $seasonId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'quantity2' => $quantity,
        ]);
    }

    $pdo->commit();
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    $pdo->rollBack();
    fail2('保存中にエラーが発生しました：' . $e->getMessage());
}
