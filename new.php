<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
$estimate_no = gen_estimate_no();
?><!doctype html><html lang="ja"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>見積作成</title>
<link rel="stylesheet" href="style.css">
</head><body>
<div class="wrapper">
  <div class="header">
    <h1>見積作成</h1>
    <div class="actions"><a class="badge" href="index.php">一覧へ</a></div>
  </div>
  <form class="card" method="post" action="save.php">
    <div class="row">
      <div class="col">
        <label class="small">顧客名</label>
        <input id="customer_name" name="customer_name" required>
      </div>
      <div class="col">
        <label class="small">見積番号</label>
        <input name="estimate_no" value="<?=h($estimate_no)?>" required>
      </div>
      <div class="col">
        <label class="small">見積先社名</label>
        <input name="recipient_company">
      </div>
      <div class="col">
        <label class="small">担当者様</label>
        <input name="recipient_person">
      </div>
    </div>

    <div class="row">
      <div class="col">
        <label class="small">見積タイトル（概要）</label>
        <input name="title">
      </div>
      <div class="col">
        <label class="small">見積形式</label>
        <select name="pricing_mode">
          <option value="unit">単価見積</option>
          <option value="lump">一式</option>
        </select>
      </div>
    </div>

    <div class="hr"></div>
    <div class="notice">
      ・合計算入チェック：外すと小計・税・合計に含めません（参考価格などに）<br>
      ・「見出し」チェック：行をセクション見出しとして扱い、金額は計算しません<br>
      ・m³は基本自動計算ですが「m³手動」をONで手入力できます
    </div>

    <!-- 編集用見出し（固定ヘッダ風） -->
    <div class="tr thead">
      <div class="smalltext">合計</div>
      <div class="smalltext">見出し</div>
      <div class="smalltext">種別</div>
      <div class="smalltext">L(mm)</div>
      <div class="smalltext">W(mm)</div>
      <div class="smalltext">H(mm)</div>
      <div class="smalltext">m³（手動可）</div>
      <div class="smalltext">M3単価</div>
      <div class="smalltext">単価</div>
      <div class="smalltext">数量</div>
      <div class="smalltext">金額</div>
      <div class="smalltext">説明</div>
      <div class="smalltext">備考</div>
      <div class="smalltext">削除</div>
    </div>

    <div id="item-list"></div>
    <div class="actions"><button type="button" id="add-row" class="btn">＋ 行を追加</button></div>

    <div class="hr"></div>
    <div class="total">
      小計：<b id="subtotal">¥0</b><br>
      消費税(10%)：<b id="tax">¥0</b><br>
      合計：<b id="total">¥0</b>
    </div>

    <div class="actions" style="justify-content:flex-end;margin-top:12px;">
      <button class="btn" type="submit">保存する</button>
    </div>
  </form>
</div>
<script src="app.js?v=8"></script>
</body></html>
