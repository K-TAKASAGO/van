<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../functions.php';
require_once __DIR__ . '/../config.php';
$pdo = db();

$estimate_no = $_POST['estimate_no'] ?? gen_estimate_no();
$customer_name = $_POST['customer_name'] ?? '';
$recipient_company = $_POST['recipient_company'] ?? '';
$recipient_person  = $_POST['recipient_person'] ?? '';
$title = $_POST['title'] ?? '';
$pricing_mode = ($_POST['pricing_mode'] ?? 'unit') === 'lump' ? 'lump' : 'unit';

$type     = $_POST['type'] ?? [];
$desc     = $_POST['description'] ?? [];
$L        = $_POST['length_mm'] ?? [];
$W        = $_POST['width_mm'] ?? [];
$H        = $_POST['height_mm'] ?? [];
$m3       = $_POST['m3'] ?? [];
$m3u      = $_POST['m3_unit_price'] ?? [];
$unit     = $_POST['unit_price'] ?? [];
$qty      = $_POST['qty'] ?? [];
$amount   = $_POST['amount'] ?? [];
$note     = $_POST['note'] ?? [];
$category = $_POST['category'] ?? [];

$incl_arr = $_POST['include_in_total'] ?? []; // checkboxes, presence = checked
$heading_arr = $_POST['is_heading'] ?? [];
$m3manual_arr = $_POST['m3_manual'] ?? [];

$items = [];
$subtotal = 0;
$rowsCount = max(count($type),count($desc));

for($i=0;$i<$rowsCount;$i++){
  $t = $type[$i] ?? '';
  $cat = $category[$i] ?? '';
  if($cat===''){ $cat = type_to_category($t); }

  $is_heading = isset($heading_arr[$i]); // order matters—checkbox arrays keep index
  $include = isset($incl_arr[$i]);
  $m3_manual = isset($m3manual_arr[$i]);

  $l = (int)round(num($L[$i]??0));
  $w = (int)round(num($W[$i]??0));
  $h = (int)round(num($H[$i]??0));

  // m3: manual or auto
  if($m3_manual){
    $m3_val = round(num($m3[$i]??0),3);
  }else{
    $m3_calc = ($l>0 && $w>0 && $h>0) ? ($l*$w*$h/1e9) : 0;
    $m3_val = round($m3_calc, 3);
  }

  $m3_unit = (int)round(num($m3u[$i]??0));
  $unit_price = (int)round(num($unit[$i]??0));
  $q = max(1, (int)round(num($qty[$i]??1)));

  if(!$is_heading && in_array($t, ['ケース梱包','クレート梱包','パレット梱包','スキッド梱包'], true)){
    if($m3_val>0 && $m3_unit>0){
      $unit_price = (int)round($m3_val * $m3_unit);
    } elseif($m3_val>0 && $unit_price>0){
      $m3_unit = (int)round($unit_price / $m3_val);
    }
  }

  $amt = $is_heading ? 0 : (int)round($unit_price * $q);

  // 空判定：見出しでも説明が無い完全空行はスキップ
  $is_empty = ($t==='' && trim((string)($desc[$i]??''))==='' && $amt===0);
  if($is_empty){ continue; }

  $row = [
    'category'=>$cat,
    'type'=>$t,
    'description'=>$desc[$i]??'',
    'length_mm'=>$l,
    'width_mm' =>$w,
    'height_mm'=>$h,
    'm3'       =>$m3_val,
    'm3_unit_price'=>$m3_unit,
    'unit_price'=>$unit_price,
    'qty'      =>$q,
    'amount'   =>$amt,
    'note'=>$note[$i]??null,
    'include_in_total'=>$include?1:0,
    'is_heading'=>$is_heading?1:0,
    'm3_manual'=>$m3_manual?1:0,
    'sort_no'=>$i+1,
  ];

  if(!$is_heading && $include){
    $subtotal += $amt;
  }
  $items[] = $row;
}

$tax = (int)round($subtotal * TAX_RATE);
$total = $subtotal + $tax;

if(empty($items)){
  http_response_code(400);
  echo '<div class="notice">明細が1件もありません。行を追加してください。</div>';
  exit;
}

$pdo->beginTransaction();
try{
  $st = $pdo->prepare("INSERT INTO estimates (estimate_no,customer_name,recipient_company,recipient_person,title,pricing_mode,subtotal,tax,total) VALUES (?,?,?,?,?,?,?,?,?)");
  $st->execute([$estimate_no,$customer_name,$recipient_company,$recipient_person,$title,$pricing_mode,$subtotal,$tax,$total]);
  $eid = (int)$pdo->lastInsertId();

  $st2 = $pdo->prepare("INSERT INTO estimate_items (estimate_id,category,type,description,length_mm,width_mm,height_mm,m3,m3_unit_price,unit_price,qty,amount,note,include_in_total,is_heading,m3_manual,sort_no) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
  foreach($items as $r){
    $st2->execute([$eid,$r['category'],$r['type'],$r['description'],$r['length_mm'],$r['width_mm'],$r['height_mm'],$r['m3'],$r['m3_unit_price'],$r['unit_price'],$r['qty'],$r['amount'],$r['note'],$r['include_in_total'],$r['is_heading'],$r['m3_manual'],$r['sort_no']]);
  }
  $pdo->commit();
  header('Location: view.php?id='.$eid);
  exit;
} catch(Throwable $e){
  $pdo->rollBack();
  http_response_code(500);
  echo '<pre style="white-space:pre-wrap;word-break:break-all">保存中にエラーが発生しました
'.h($e->getMessage())."
".$e->getTraceAsString().'</pre>';
}
