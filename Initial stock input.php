<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$season = get_active_season();
if (!$season) {
    echo '現在アクティブなシーズンが設定されていません。';
    exit;
}

$pdo = get_pdo();
$stmt = $pdo->prepare("
  SELECT p.id AS product_id, p.product_code, p.product_name, g.name AS genre_name,
         COALESCE(ist.quantity, 0) AS initial_stock
  FROM products p
  INNER JOIN genres g ON g.id = p.genre_id
  LEFT JOIN initial_stocks ist ON ist.product_id = p.id AND ist.season_id = :season_id
  WHERE p.is_active = 1
  ORDER BY g.display_order ASC, g.id ASC, p.display_order ASC, p.id ASC
");
$stmt->execute(['season_id' => $season['id']]);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>開始時在庫の一括入力 - シーズン受注台帳</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Hiragino Kaku Gothic ProN", "Yu Gothic", sans-serif; background: #f5f5f5; color: #222; }
  .page { max-width: 760px; margin: 0 auto; padding: 24px 16px; }
  .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
  .header h1 { font-size: 18px; font-weight: 600; }
  .header .season { font-size: 13px; color: #666; background: #fff; padding: 4px 10px; border-radius: 999px; }
  .note { font-size: 13px; color: #666; margin-bottom: 16px; }
  table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
  th { text-align: left; font-size: 12px; color: #888; padding: 10px 14px; border-bottom: 1px solid #eee; }
  td { padding: 8px 14px; border-bottom: 1px solid #eee; font-size: 14px; }
  td.genre { color: #888; font-size: 12px; white-space: nowrap; }
  td.code { color: #999; font-size: 12px; white-space: nowrap; }
  input.stock-input { width: 90px; height: 32px; border-radius: 6px; border: 1px solid #ccc; padding: 0 8px; text-align: right; font-size: 14px; }
  input.stock-input:focus { outline: none; border-color: #4a90d9; }
  .actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 18px; }
  .btn-primary { height: 42px; padding: 0 24px; border-radius: 8px; border: none; background: #222; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; }
  .msg { margin-top: 12px; font-size: 13px; padding: 10px 14px; border-radius: 8px; display: none; }
  .msg.success { background: #e6f4ea; color: #1e7e34; display: block; }
  .msg.error { background: #fdecea; color: #c0392b; display: block; }
</style>
</head>
<body>
<div class="page">
  <div class="header">
    <h1>開始時在庫の一括入力</h1>
    <span class="season"><?= htmlspecialchars($season['name']) ?></span>
  </div>
  <p class="note">シーズン開始時点で手元にある在庫数を商品ごとに入力してください。あとから商品詳細ページで個別に修正することもできます。</p>

  <table>
    <thead>
      <tr><th>ジャンル</th><th>商品名</th><th>コード</th><th>開始時在庫</th></tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
      <tr>
        <td class="genre"><?= htmlspecialchars($p['genre_name']) ?></td>
        <td><?= htmlspecialchars($p['product_name']) ?></td>
        <td class="code"><?= htmlspecialchars($p['product_code']) ?></td>
        <td>
          <input type="number" class="stock-input" min="0"
                 data-product-id="<?= (int)$p['product_id'] ?>"
                 value="<?= (int)$p['initial_stock'] ?>">
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="actions">
    <button class="btn-primary" id="save-btn">まとめて保存する</button>
  </div>
  <div class="msg" id="msg"></div>
</div>

<script>
const SEASON_ID = <?= (int)$season['id'] ?>;

document.getElementById('save-btn').addEventListener('click', async () => {
  const msg = document.getElementById('msg');
  msg.className = 'msg';

  const stocks = [];
  document.querySelectorAll('.stock-input').forEach(input => {
    stocks.push({
      product_id: parseInt(input.dataset.productId),
      quantity: parseInt(input.value) || 0,
    });
  });

  try {
    const res = await fetch('../api/save_initial_stocks.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ season_id: SEASON_ID, stocks }),
    });
    const result = await res.json();
    if (result.ok) {
      msg.className = 'msg success';
      msg.textContent = '保存しました。';
    } else {
      msg.className = 'msg error';
      msg.textContent = '保存に失敗しました：' + (result.error || '不明なエラー');
    }
  } catch (e) {
    msg.className = 'msg error';
    msg.textContent = '通信エラーが発生しました。';
  }
});
</script>
</body>
</html>
