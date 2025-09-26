<?php
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function yen($n): string { return '¥' . number_format((float)$n); }
function num($v): float { return is_numeric($v) ? (float)$v : 0.0; }
function gen_estimate_no(): string { return 'Q' . date('Ymd-His'); }
function type_to_category(string $type): string {
  $map = [
    'ケース梱包' => '梱包',
    'クレート梱包' => '梱包',
    'パレット梱包' => '梱包',
    'スキッド梱包' => '梱包',
    '付帯作業' => '梱包',
    '取扱料' => '梱包',
    '搬入' => '運送',
    '引取' => '運送',
    '梱包対象フリー期間有' => '保管',
    '梱包対象外フリー期間無' => '保管',
    'バンニング' => 'コンテナー作業',
    'ショワリング' => 'コンテナー作業',
    'ドレージ' => 'コンテナー作業',
    'その他' => 'その他',
  ];
  return $map[$type] ?? 'その他';
}
