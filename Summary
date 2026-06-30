<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$seasons = get_all_seasons();
$activeSeason = get_active_season();
$selectedSeasonId = isset($_GET['season_id']) ? (int)$_GET['season_id'] : ($activeSeason['id'] ?? 0);

if (!$selectedSeasonId) {
    echo 'シーズンが登録されていません。先にシーズンを作成してください。';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品別受注集計 - シーズン受注台帳</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Hiragino Kaku Gothic ProN", "Yu Gothic", sans-serif; background: #f5f5f5; color: #222; }
  .page { max-width: 1200px; margin: 0 auto; padding: 24px 16px; }
  .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
  .topbar h1 { font-size: 18px; font-weight: 600; }
  .filters { display: flex; gap: 8px; align-items: center; }
  .filters select { height: 36px; border-radius: 8px; border: 1px solid #ccc; padding: 0 10px; font-size: 13px; background: #fff; }
  .btn-excel { height: 36px; padding: 0 16px; border-radius: 8px; border: 1px solid #2b6cb0; background: #eef5fc; color: #2b6cb0; font-size: 13px; font-weight: 600; cursor: pointer; }
  .alert-banner { display: none; align-items: center; gap: 8px; background: #fdecea; color: #c0392b; border-radius: 8px; padding: 10px 16px; font-size: 13px; margin-bottom: 16px; }
  .alert-banner.show { display: flex; }
  table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05); font-size: 13px; }
  th { text-align: left; font-size: 11px; font-weight: 600; color: #888; padding: 10px 14px; border-bottom: 1px solid #eee; white-space: nowrap; }
  td { padding: 11px 14px; border-bottom: 1px solid #eee; vertical-align: middle; }
  tr.urgent td { background: #fdecea; }
  tr:not(.urgent):hover td { background: #fafafa; }
  .num { text-align: right; font-variant-numeric: tabular-nums; }
  .stock-num { font-weight: 700; }
  .stock-minus { color: #c0392b; }
  .stock-plus { color: #222; }
  .genre-tag { font-size: 11px; color: #888; background: #f0f0f0; padding: 3px 9px; border-radius: 999px; white-space: nowrap; }
  .action-btn { font-size: 12px; padding: 5px 12px; border-radius: 999px; border: none; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
  .btn-done { background: #e6f4ea; color: #1e7e34; }
  .btn-needed { background: #c0392b; color: #fff; }
  .product-cell { display: flex; flex-direction: column; gap: 3px; cursor: pointer; }
  .product-name-row { display: flex; align-items: center; gap: 10px; }
  .product-name { font-weight: 600; color: #2b6cb0; }
  .code-chip { font-size: 11px; color: #888; background: #f5f5f5; border: 1px solid #ddd; padding: 2px 8px; border-radius: 999px; display: inline-flex; align-items: center; gap: 4px; cursor: pointer; white-space: nowrap; }
  .code-chip:hover { background: #ececec; }
  .code-chip.copied { background: #e6f4ea; border-color: #b7d9bf; color: #1e7e34; }
  .loading { padding: 40px; text-align: center; color: #999; }
  .copy-toast { position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(10px); background: #222; color: #fff; font-size: 12px; padding: 8px 16px; border-radius: 8px; opacity: 0; transition: all 0.2s; pointer-events: none; z-index: 100; }
  .copy-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
</style>
</head>
<body>
<div class="page">
  <div class="topbar">
    <h1>商品別 受注集計</h1>
    <div class="filters">
      <select id="season-select">
        <?php foreach ($seasons as $s): ?>
          <option value="<?= (int)$s['id'] ?>" <?= $s['id'] == $selectedSeasonId ? 'selected' : '' ?>>
            <?= htmlspecialchars($s['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select id="filter-select">
        <option value="all">すべて表示</option>
        <option value="needed">未発注のみ</option>
      </select>
      <button class="btn-excel" id="excel-btn">Excel出力</button>
    </div>
  </div>

  <div class="alert-banner" id="alert-banner">
    <span id="alert-text"></span>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:130px;">ジャンル</th>
        <th>商品名 ／ コード</th>
        <th class="num" style="width:100px;">受注件数</th>
        <th class="num" style="width:100px;">発注件数</th>
        <th class="num" style="width:90px;">在庫</th>
        <th style="width:110px;">状態</th>
      </tr>
    </thead>
    <tbody id="summary-tbody">
      <tr><td colspan="6" class="loading">読み込み中...</td></tr>
    </tbody>
  </table>
</div>

<div class="copy-toast" id="copy-toast">コードをコピーしました</div>

<script>
const seasonSelect = document.getElementById('season-select');
const filterSelect = document.getElementById('filter-select');
const tbody = document.getElementById('summary-tbody');
const alertBanner = document.getElementById('alert-banner');
const alertText = document.getElementById('alert-text');

async function loadSummary() {
  const seasonId = seasonSelect.value;
  tbody.innerHTML = '<tr><td colspan="6" class="loading">読み込み中...</td></tr>';

  try {
    const res = await fetch(`../api/get_summary.php?season_id=${seasonId}`);
    const result = await res.json();
    if (!result.ok) {
      tbody.innerHTML = `<tr><td colspan="6" class="loading">読み込みに失敗しました</td></tr>`;
      return;
    }
    renderTable(result.data);
  } catch (e) {
    tbody.innerHTML = `<tr><td colspan="6" class="loading">通信エラーが発生しました</td></tr>`;
  }
}

function renderTable(data) {
  const filter = filterSelect.value;
  const filtered = filter === 'needed' ? data.filter(d => d.status === 'needed') : data;

  const neededCount = data.filter(d => d.status === 'needed').length;
  if (neededCount > 0) {
    alertBanner.classList.add('show');
    alertText.textContent = `${neededCount}件 在庫不足で未発注の商品があります。発注漏れに注意してください（行の並び順は固定です）`;
  } else {
    alertBanner.classList.remove('show');
  }

  if (filtered.length === 0) {
    tbody.innerHTML = '<tr><td colspan="6" class="loading">対象データがありません</td></tr>';
    return;
  }

  tbody.innerHTML = filtered.map(row => {
    const isUrgent = row.status === 'needed';
    const stockClass = row.stock < 0 ? 'stock-minus' : 'stock-plus';
    const stockDisplay = row.stock;
    const statusBtn = isUrgent
      ? `<button class="action-btn btn-needed">未発注</button>`
      : `<button class="action-btn btn-done">発注済</button>`;

    return `
      <tr class="${isUrgent ? 'urgent' : ''}">
        <td><span class="genre-tag">${escapeHtml(row.genre_name)}</span></td>
        <td>
          <div class="product-cell" onclick="goDetail(${row.product_id})">
            <div class="product-name-row">
              <span class="product-name">${escapeHtml(row.product_name)}</span>
              <span class="code-chip" onclick="copyCode(event, '${row.product_code}', this)">
                ${escapeHtml(row.product_code)}
              </span>
            </div>
          </div>
        </td>
        <td class="num">${row.order_count}</td>
        <td class="num">${row.po_count}</td>
        <td class="num stock-num ${stockClass}">${stockDisplay}</td>
        <td>${statusBtn}</td>
      </tr>
    `;
  }).join('');
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function copyCode(event, code, el) {
  event.stopPropagation();
  navigator.clipboard.writeText(code).catch(() => {});
  el.classList.add('copied');
  const toast = document.getElementById('copy-toast');
  toast.classList.add('show');
  setTimeout(() => el.classList.remove('copied'), 1000);
  setTimeout(() => toast.classList.remove('show'), 1400);
}

function goDetail(productId) {
  window.location.href = `product_detail.php?product_id=${productId}&season_id=${seasonSelect.value}`;
}

seasonSelect.addEventListener('change', () => {
  const url = new URL(window.location);
  url.searchParams.set('season_id', seasonSelect.value);
  window.history.replaceState({}, '', url);
  loadSummary();
});
filterSelect.addEventListener('change', loadSummary);

document.getElementById('excel-btn').addEventListener('click', () => {
  window.location.href = `../api/export_excel.php?season_id=${seasonSelect.value}`;
});

loadSummary();
</script>
</body>
</html>
