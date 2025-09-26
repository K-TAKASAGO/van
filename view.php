<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
$est = $pdo->prepare("SELECT * FROM estimates WHERE id=?");
$est->execute([$id]);
$est = $est->fetch();
if(!$est){ http_response_code(404); exit('not found'); }
$it = $pdo->prepare("SELECT * FROM estimate_items WHERE estimate_id=? ORDER BY sort_no,id");
$it->execute([$id]);
$items = $it->fetchAll();

$order = ['梱包','運送','保管','コンテナー作業','その他'];
$grouped = [];
foreach($order as $cat){ $grouped[$cat] = []; }
foreach($items as $r){
  $cat = $r['category'];
  if(!array_key_exists($cat,$grouped)) $grouped[$cat]=[];
  $grouped[$cat][] = $r;
}

function hyphenize($v){
  if($v===null) return '<span class="muted">ー</span>';
  if(is_numeric($v)){
    if((float)$v==0.0) return '<span class="muted">ー</span>';
  } else {
    if(trim($v)==='') return '<span class="muted">ー</span>';
  }
  return h((string)$v);
}
?><!doctype html><html lang="ja"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h($est['estimate_no'])?> 見積書</title>
<link rel="stylesheet" href="style.css">
<style>
.print{background:white;color:black;padding:20px;border-radius:0}
.print h2{margin:0 0 8px}
.print table{width:100%;border-collapse:collapse;table-layout:fixed}
.print thead th{background:#e8efff}
.print th,.print td{border:1px solid #333;padding:6px;font-size:12px;word-break:break-word;vertical-align:top}
.section-row td{background:#f1f5f9;font-weight:700}
@media print{.noprint{display:none}}
</style>
</head><body>
<div class="wrapper">
  <div class="header noprint">
    <h1>見積表示</h1>
    <div class="actions">
      <a class="badge" href="index.php">一覧</a>
      <button class="btn" onclick="window.print()">印刷</button>
    </div>
  </div>
  <div class="card print">
    <h2>見積書</h2>
    <div>見積番号：<?=h($est['estimate_no'])?></div>
    <div>顧客名：<?=h($est['customer_name'])?></div>
    <?php if($est['recipient_company']): ?><div>見積先：<?=h($est['recipient_company'])?> <?=h($est['recipient_person'])?> 様</div><?php endif; ?>
    <div>件名：<?=h($est['title']??'')?></div>
    <div>見積形式：<?=h($est['pricing_mode']==='lump'?'一式':'単価見積')?></div>
    <br>
    <?php foreach($order as $cat): if(empty($grouped[$cat])) continue; ?>
      <div class="cat-title">■ <?=h($cat)?></div>
      <table>
        <colgroup>
          <col style="width:10%"><col style="width:24%"><col style="width:6%"><col style="width:6%"><col style="width:6%">
          <col style="width:7%"><col style="width:8%"><col style="width:8%"><col style="width:6%"><col style="width:9%"><col style="width:10%">
        </colgroup>
        <thead><tr><th>種別</th><th>説明</th><th>L</th><th>W</th><th>H</th><th>m3</th><th>M3単価</th><th>単価</th><th>数量</th><th>金額</th><th>備考</th></tr></thead>
        <tbody>
        <?php foreach($grouped[$cat] as $r): ?>
          <?php if((int)$r['is_heading']===1): ?>
            <tr class="section-row"><td colspan="11"><?=h($r['description']?:'見出し')?></td></tr>
          <?php else: ?>
            <tr>
              <td><?=h($r['type'])?></td>
              <td><?=hyphenize($r['description'])?></td>
              <td><?=hyphenize((int)$r['length_mm'])?></td>
              <td><?=hyphenize((int)$r['width_mm'])?></td>
              <td><?=hyphenize((int)$r['height_mm'])?></td>
              <td><?=(float)$r['m3']>0?number_format((float)$r['m3'],3):'<span class="muted">ー</span>'?></td>
              <td><?=(int)$r['m3_unit_price']>0?yen((int)$r['m3_unit_price']):'<span class="muted">ー</span>'?></td>
              <td><?=(int)$r['unit_price']>0?yen((int)$r['unit_price']):'<span class="muted">ー</span>'?></td>
              <td><?=(int)$r['qty']>0?((int)$r['qty']):'<span class="muted">ー</span>'?></td>
              <td><?=(int)$r['amount']>0?yen((int)$r['amount']):'<span class="muted">ー</span>'?></td>
              <td><?=hyphenize($r['note'])?></td>
            </tr>
          <?php endif; ?>
        <?php endforeach; ?>
        </tbody>
      </table>
      <br>
    <?php endforeach; ?>
    <div style="text-align:right;margin-top:10px;">
      小計：<?=yen((int)$est['subtotal'])?><br>
      税金：<?=yen((int)$est['tax'])?><br>
      合計：<b><?=yen((int)$est['total'])?></b>
    </div>
  </div>
</div>
</body></html>
