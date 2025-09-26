<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
$pdo = db();
$rows = $pdo->query("SELECT * FROM estimates ORDER BY id DESC LIMIT 200")->fetchAll();
?><!doctype html><html lang="ja"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>見積一覧</title>
<link rel="stylesheet" href="style.css">
</head><body>
<div class="wrapper">
  <div class="header">
    <h1>見積一覧</h1>
    <div class="actions"><a class="btn" href="new.php">＋ 新規作成</a></div>
  </div>
  <div class="card">
    <table class="table" style="width:100%">
      <thead><tr><th>作成日</th><th>見積番号</th><th>顧客</th><th>件名</th><th>金額(税別)</th><th>操作</th></tr></thead>
      <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?=h($r['created_at'])?></td>
          <td><?=h($r['estimate_no'])?></td>
          <td><?=h($r['customer_name'])?></td>
          <td><?=h($r['title']??'')?></td>
          <td><?=yen($r['subtotal'])?></td>
          <td>
            <a class="badge" href="view.php?id=<?=$r['id']?>">表示/印刷</a>
            <a class="badge" href="delete.php?id=<?=$r['id']?>" onclick="return confirm('削除しますか？');">削除</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body></html>
