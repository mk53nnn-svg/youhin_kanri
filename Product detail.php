<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$seasonId = isset($_GET['season_id']) ? (int)$_GET['season_id'] : 0;

if ($productId <= 0 || $seasonId <= 0) {
    echo 'パラメータが不足しています。';
    exit;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品詳細 - シーズン受注台帳</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, "Hiragino Kaku Gothic ProN", "Yu Gothic", sans-serif; background: #f5f5f5; color: #222; }
  .page { max-width: 760px; margin: 0 auto; padding: 24px 16px; }
  .breadcrumb { font-size: 12px; color: #888; margin-bottom: 12px; cursor: pointer; }
  .breadcrumb:hover { color: #2b6cb0; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; }
  .header h1 { font-size: 19px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
  .code { font-size: 12px; color: #888; font-weight: 400; }
  .genre-tag { font-size: 11px; color: #888; background: #f0f0f0; padding: 3px 9px; border-radius: 999px; }

  .stat-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 18px; }
  .card { background: #fff; border-radius: 10px; padding: 14px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
  .card.clickable { cursor: pointer; position: relative; }
  .card .label { font-size: 11px; color: #888; margin-bottom: 4px; }
  .card .value { font-size: 22px; font-weight: 700; }
  .card.danger .value { color: #c0392b; }
  .card.success .value { color: #1e7e34; }

  .po-history { display: none; background: #fff; border-radius: 10px; padding: 12px 14px; margin-bottom: 18px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
  .po-history.show { display: block; }
  .po-history table { width: 100%; font-size: 13px; }
  .po-history th { text-align: left; font-size: 11px; color: #888; padding: 4px 8px; }
  .po-history td { padding: 5px 8px; border-top: 1px solid #f0f0f0; }

  .stock-panel { background: #fff; border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
  .stock-panel-title { font-size: 13px; font-weight: 600; color: #555; margin-bottom: 8px; }
  .stock-row { display: flex; gap: 10px; align-items: center; }
  .stock-row input { width: 100px; height: 34px; border-radius: 6px; border: 1px solid #ccc; padding: 0 8px; font-size: 14px; }
  .btn-mini-save { height: 34px; padding: 0 14px; border-radius: 6px; border: none; background: #222; color: #fff; font-size: 12px; cursor: pointer; }

  .order-panel { background: #fff; border-radius: 10px; padding: 14px 16px; margin-bottom: 18px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
  .order-panel-title { font-size: 13px; font-weight: 600; color: #555; margin-bottom: 10px; }
  .order-row { display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap; }
  .field { display: flex; flex-direction: column; gap: 4px; flex: 1; min-width: 130px; }
  .field label { font-size: 12px; color: #555; }
  .field input { height: 36px; border-radius: 6px; border: 1px solid #ccc; padding: 0 10px; font-size: 14px; }
  .btn-save { height: 36px; padding: 0 18px; border-radius: 6px; border: none; background: #222; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; }
  .saved-msg { font-size: 12px; color: #1e7e34; margin-top: 8px; display: none; }
  .saved-msg.show { display: block; }

  .section-title { font-size: 13px; font-weight: 600; color: #555; margin-bottom: 8px; margin-top: 18px; }
  table.main-table { width: 100%; border-collapse: collapse; font-size: 13px; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
  table.main-table th { text-align: left; font-size: 11px; font-weight: 600; color: #888; padding: 8px 12px; border-bottom: 1px solid #eee; }
  table.main-table td { padding: 9px 12px; border-bottom: 1px solid #eee; }
  .num { text-align: right; }
  .edit-btn { font-size: 11px; color: #2b6cb0; border: 1px solid #2b6cb0; background: #eef5fc; border-radius: 6px; padding: 3px 9px; cursor: pointer; }
  .total-row td { font-weight: 700; background: #fafafa; }
  .edit-row input, .edit-row select { height: 30px; border-radius: 6px; border: 1px solid #ccc; padding: 0 6px; font-size: 12px; width: 100%; }
  .edit-actions { display: flex; gap: 6px; }
  .btn-mini { font-size: 11px; padding: 3px 8px; border-radius: 6px; border: none; cursor: pointer; }
  .btn-mini-confirm { background: #222; color: #fff; }
  .btn-mini-cancel { background: #fff; border: 1px solid #ccc; color: #888; }
  .loading { padding: 30px; text-align: center; color: #999; }
</style>
</head>
<body>
<div class="page" id="page-root">
  <div class="breadcrumb" onclick="goBack()">← 商品別受注集計に戻る</div>
  <div class="loading" id="loading">読み込み中...</div>
  <div id="content" style="display:none;"></div>
</div>

<script>
const PRODUCT_ID = <?= (int)$productId ?>;
const SEASON_ID = <?= (int)$seasonId ?>;

let currentData = null;

function goBack() {
  window.location.href = `summary.php?season_id=${SEASON_ID}`;
}

async function loadDetail() {
  const res = await fetch(`../api/get_product_detail.php?product_id=${PRODUCT_ID}&season_id=${SEASON_ID}`);
  const result = await res.json();
  if (!result.ok) {
    document.getElementById('loading').textContent = '読み込みに失敗しました：' + (result.error || '');
    return;
  }
  currentData = result;
  render();
}

function render() {
  const d = currentData;
  document.getElementById('loading').style.display = 'none';
  const content = document.getElementById('content');
  content.style.display = 'block';

  const stockClass = d.stock < 0 ? 'danger' : 'success';

  content.innerHTML = `
    <div class="header">
      <h1>${escapeHtml(d.product.product_name)} <span class="code">${escapeHtml(d.product.product_code)}</span></h1>
      <span class="genre-tag">${escapeHtml(d.product.genre_name)}</span>
    </div>

    <div class="stat-cards">
      <div class="card"><div class="label">受注合計</div><div class="value">${d.order_total}</div></div>
      <div class="card clickable" onclick="togglePoHistory()">
        <div class="label">発注済（クリックで履歴）</div><div class="value">${d.po_total}</div>
      </div>
      <div class="card ${stockClass}"><div class="label">在庫（開始時+発注-受注）</div><div class="value">${d.stock}</div></div>
    </div>

    <div class="po-history" id="po-history">
      <table>
        <thead><tr><th>発注日</th><th class="num">発注数</th></tr></thead>
        <tbody>
          ${d.purchase_orders.length === 0
            ? '<tr><td colspan="2" style="color:#999;">発注履歴はまだありません</td></tr>'
            : d.purchase_orders.map(po => `<tr><td>${po.order_date}</td><td class="num">${po.quantity}</td></tr>`).join('')}
        </tbody>
      </table>
    </div>

    <div class="stock-panel">
      <div class="stock-panel-title">シーズン開始時在庫（個別調整）</div>
      <div class="stock-row">
        <input type="number" id="initial-stock-input" min="0" value="${d.initial_stock}">
        <button class="btn-mini-save" onclick="saveInitialStock()">保存</button>
        <span class="saved-msg" id="stock-saved-msg">保存しました</span>
      </div>
    </div>

    <div class="order-panel">
      <div class="order-panel-title">発注入力</div>
      <div class="order-row">
        <div class="field">
          <label>発注数</label>
          <input type="number" id="po-qty-input" min="1" placeholder="例：15">
        </div>
        <div class="field">
          <label>発注日</label>
          <input type="date" id="po-date-input" value="${new Date().toISOString().slice(0,10)}">
        </div>
        <button class="btn-save" onclick="savePurchaseOrder()">保存する</button>
      </div>
      <div class="saved-msg" id="po-saved-msg">保存しました。発注履歴に追加されました</div>
    </div>

    <div class="section-title">受注内訳（${d.orders.length}件）</div>
    <table class="main-table">
      <thead>
        <tr><th>受注日</th><th>取引先</th><th>納期</th><th class="num">受注数</th><th></th></tr>
      </thead>
      <tbody id="order-tbody">
        ${d.orders.map(o => renderOrderRow(o)).join('')}
        <tr class="total-row">
          <td colspan="3" style="text-align:right;color:#888;font-size:12px;">合計</td>
          <td class="num" id="total-qty">${d.order_total}</td>
          <td></td>
        </tr>
      </tbody>
    </table>
  `;
}

function renderOrderRow(o) {
  return `
    <tr data-id="${o.id}">
      <td class="td-date">${o.order_date}</td>
      <td class="td-client">${escapeHtml(o.client_name)}</td>
      <td class="td-deadline" data-type="${o.delivery_type}" data-date="${o.delivery_date || ''}">${o.delivery_label}</td>
      <td class="num td-qty">${o.quantity}</td>
      <td><button class="edit-btn" onclick="startEdit(this)">編集</button></td>
    </tr>
  `;
}

function togglePoHistory() {
  document.getElementById('po-history').classList.toggle('show');
}

async function saveInitialStock() {
  const qty = parseInt(document.getElementById('initial-stock-input').value) || 0;
  await fetch('../api/save_initial_stocks.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ season_id: SEASON_ID, stocks: [{ product_id: PRODUCT_ID, quantity: qty }] }),
  });
  document.getElementById('stock-saved-msg').classList.add('show');
  setTimeout(() => document.getElementById('stock-saved-msg').classList.remove('show'), 1500);
  await loadDetail();
}

async function savePurchaseOrder() {
  const qty = parseInt(document.getElementById('po-qty-input').value) || 0;
  const date = document.getElementById('po-date-input').value;
  if (qty <= 0 || !date) return;

  await fetch('../api/add_purchase_order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ season_id: SEASON_ID, product_id: PRODUCT_ID, order_date: date, quantity: qty }),
  });
  document.getElementById('po-saved-msg').classList.add('show');
  document.getElementById('po-qty-input').value = '';
  await loadDetail();
}

function startEdit(btn) {
  const row = btn.closest('tr');
  const date = row.querySelector('.td-date').textContent;
  const client = row.querySelector('.td-client').textContent;
  const deadlineCell = row.querySelector('.td-deadline');
  const deliveryType = deadlineCell.dataset.type;
  const deliveryDate = deadlineCell.dataset.date;
  const qty = row.querySelector('.td-qty').textContent;

  row.classList.add('edit-row');
  row.querySelector('.td-date').innerHTML = `<input type="date" class="e-date" value="${date}">`;
  row.querySelector('.td-client').innerHTML = `<input type="text" class="e-client" value="${client}">`;
  row.querySelector('.td-deadline').innerHTML = `
    <select class="e-delivery-type">
      <option value="date" ${deliveryType==='date'?'selected':''}>日付指定</option>
      <option value="即納" ${deliveryType==='即納'?'selected':''}>即納</option>
      <option value="初旬" ${deliveryType==='初旬'?'selected':''}>初旬</option>
      <option value="中旬" ${deliveryType==='中旬'?'selected':''}>中旬</option>
      <option value="下旬" ${deliveryType==='下旬'?'selected':''}>下旬</option>
    </select>
    <input type="date" class="e-delivery-date" value="${deliveryDate}" style="margin-top:4px;">
  `;
  row.querySelector('.td-qty').innerHTML = `<input type="number" class="e-qty" value="${qty}" style="text-align:right;">`;
  row.children[4].innerHTML = `
    <div class="edit-actions">
      <button class="btn-mini btn-mini-confirm" onclick="confirmEdit(this)">保存</button>
      <button class="btn-mini btn-mini-cancel" onclick="cancelEdit(this)">取消</button>
    </div>`;
}

async function confirmEdit(btn) {
  const row = btn.closest('tr');
  const orderId = parseInt(row.dataset.id);
  const orderDate = row.querySelector('.e-date').value;
  const clientName = row.querySelector('.e-client').value;
  const deliveryType = row.querySelector('.e-delivery-type').value;
  const deliveryDate = row.querySelector('.e-delivery-date').value;
  const quantity = parseInt(row.querySelector('.e-qty').value) || 0;

  await fetch('../api/update_order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      id: orderId, client_name: clientName, order_date: orderDate,
      delivery_type: deliveryType, delivery_date: deliveryType === 'date' ? deliveryDate : null,
      quantity: quantity,
    }),
  });
  await loadDetail();
}

function cancelEdit() {
  render(); // 再描画して編集前の状態に戻す
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

loadDetail();
</script>
</body>
</html>
