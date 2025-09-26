<?php
require_once __DIR__ . '/../db.php';
$pdo = db();
$id = (int)($_GET['id'] ?? 0);
if($id){
  $st=$pdo->prepare('DELETE FROM estimates WHERE id=?');
  $st->execute([$id]);
}
header('Location: index.php');
