<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/functions.php';

$seasonId = isset($_GET['season_id']) ? (int)$_GET['season_id'] : 0;
if ($seasonId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'season_id が指定されていません。'], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = get_pdo();

// 商品マスタ（ジャンル付き、表示順固定）に対して、受注集計・発注集計をLEFT JOINする
$sql = "
  SELECT
    p.id            AS product_id,
    p.product_code,
    p.product_name,
    p.display_order AS product_order,
    g.id            AS genre_id,
    g.name          AS genre_name,
    g.display_order AS genre_order,
    COALESCE(o.order_count, 0)    AS order_count,
    COALESCE(o.order_qty_sum, 0)  AS order_qty_sum,
    COALESCE(po.po_qty_sum, 0)    AS po_qty_sum,
    COALESCE(po.po_count, 0)      AS po_count,
    COALESCE(ist.quantity, 0)     AS initial_stock
  FROM products p
  INNER JOIN genres g ON g.id = p.genre_id
  LEFT JOIN (
    SELECT product_id, COUNT(*) AS order_count, SUM(quantity) AS order_qty_sum
    FROM orders
    WHERE season_id = :season_id1
    GROUP BY product_id
  ) o ON o.product_id = p.id
  LEFT JOIN (
    SELECT product_id, COUNT(*) AS po_count, SUM(quantity) AS po_qty_sum
    FROM purchase_orders
    WHERE season_id = :season_id2
    GROUP BY product_id
  ) po ON po.product_id = p.id
  LEFT JOIN initial_stocks ist
    ON ist.product_id = p.id AND ist.season_id = :season_id3
  WHERE p.is_active = 1
  ORDER BY g.display_order ASC, g.id ASC, p.display_order ASC, p.id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['season_id1' => $seasonId, 'season_id2' => $seasonId, 'season_id3' => $seasonId]);
$rows = $stmt->fetchAll();

$result = array_map(function ($row) {
    $orderQty = (int)$row['order_qty_sum'];
    $poQty = (int)$row['po_qty_sum'];
    $initialStock = (int)$row['initial_stock'];
    $stock = $initialStock + $poQty - $orderQty; // 在庫 = 開始時在庫 + 発注数 − 受注数
    return [
        'product_id' => (int)$row['product_id'],
        'product_code' => $row['product_code'],
        'product_name' => $row['product_name'],
        'genre_name' => $row['genre_name'],
        'order_count' => (int)$row['order_count'],
        'order_qty_sum' => $orderQty,
        'po_count' => (int)$row['po_count'],
        'po_qty_sum' => $poQty,
        'initial_stock' => $initialStock,
        'stock' => $stock,
        'status' => $stock >= 0 ? 'ordered' : 'needed', // ordered=発注済, needed=未発注
    ];
}, $rows);

echo json_encode(['ok' => true, 'data' => $result], JSON_UNESCAPED_UNICODE);
