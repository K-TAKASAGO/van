(function(){
  'use strict';
  var TAX_RATE = 0.10;
  var M3_TYPES = new Set(['ケース梱包','クレート梱包','パレット梱包','スキッド梱包']);
  var TYPE_TO_CATEGORY = {
    'ケース梱包':'梱包','クレート梱包':'梱包','パレット梱包':'梱包','スキッド梱包':'梱包','付帯作業':'梱包','取扱料':'梱包',
    '搬入':'運送','引取':'運送',
    '梱包対象フリー期間有':'保管','梱包対象外フリー期間無':'保管',
    'バンニング':'コンテナー作業','ショワリング':'コンテナー作業','ドレージ':'コンテナー作業',
    'その他':'その他'
  };

  function num(v){ var n=parseFloat(v); return isFinite(n)?n:0; }
  function yenInt(n){ return '¥'+(Math.round(n)).toLocaleString(); }

  function newRow(data){
    data = data || {};
    var row = document.createElement('div');
    row.className = 'tr';
    row.innerHTML =
      '<input type="hidden" name="category[]"/>' +
      '<input type="checkbox" name="include_in_total[]" class="incl" checked title="合計に算入">' +
      '<label class="smalltext"><input type="checkbox" name="is_heading[]" class="heading"> 見出し</label>' +
      '<select name="type[]" class="type">' +
      '<optgroup label="梱包">' +
      '<option value="ケース梱包">ケース梱包</option>' +
      '<option value="クレート梱包">クレート梱包</option>' +
      '<option value="パレット梱包">パレット梱包</option>' +
      '<option value="スキッド梱包">スキッド梱包</option>' +
      '<option value="付帯作業">付帯作業</option>' +
      '<option value="取扱料">取扱料</option>' +
      '</optgroup>' +
      '<optgroup label="運送">' +
      '<option value="搬入">搬入</option>' +
      '<option value="引取">引取</option>' +
      '</optgroup>' +
      '<optgroup label="保管">' +
      '<option value="梱包対象フリー期間有">梱包対象フリー期間有</option>' +
      '<option value="梱包対象外フリー期間無">梱包対象外フリー期間無</option>' +
      '</optgroup>' +
      '<optgroup label="コンテナー作業">' +
      '<option value="バンニング">バンニング</option>' +
      '<option value="ショワリング">ショワリング</option>' +
      '<option value="ドレージ">ドレージ</option>' +
      '</optgroup>' +
      '<optgroup label="その他">' +
      '<option value="その他">その他</option>' +
      '</optgroup>' +
      '</select>' +
      '<input name="length_mm[]" type="number" step="1" min="0" placeholder="L"/>' +
      '<input name="width_mm[]"  type="number" step="1" min="0" placeholder="W"/>' +
      '<input name="height_mm[]" type="number" step="1" min="0" placeholder="H"/>' +
      '<div>' +
      '  <label class="smalltext"><input type="checkbox" class="m3manual" name="m3_manual[]"> m³手動</label>' +
      '  <input name="m3[]" type="number" step="0.001" placeholder="m³" readonly/>' +
      '</div>' +
      '<input name="m3_unit_price[]" type="number" step="1" min="0" placeholder="M3単価"/>' +
      '<input name="unit_price[]"   type="number" step="1" min="0" placeholder="単価"/>' +
      '<input name="qty[]"          type="number" step="1" min="1" value="1" placeholder="数量"/>' +
      '<input name="amount[]"       type="number" step="1" placeholder="金額" readonly/>' +
      '<input name="description[]" placeholder="説明（車種・場所などもここに）"/>' +
      '<input name="note[]"    placeholder="備考"/>' +
      '<button type="button" class="btn del" aria-label="行を削除">×</button>';

    Object.keys(data).forEach(function(k){
      var el = row.querySelector('[name="'+k+'[]"]');
      if(el){ el.value = data[k]; }
    });

    bindRow(row);
    return row;
  }

  function bindRow(row){
    var type = row.querySelector('.type');
    var catHidden = row.querySelector('[name="category[]"]');
    var L = row.querySelector('[name="length_mm[]"]');
    var W = row.querySelector('[name="width_mm[]"]');
    var H = row.querySelector('[name="height_mm[]"]');
    var m3 = row.querySelector('[name="m3[]"]');
    var m3u= row.querySelector('[name="m3_unit_price[]"]');
    var unit=row.querySelector('[name="unit_price[]"]');
    var qty = row.querySelector('[name="qty[]"]');
    var amt = row.querySelector('[name="amount[]"]');
    var incl= row.querySelector('.incl');
    var heading = row.querySelector('.heading');
    var m3manual = row.querySelector('.m3manual');

    function syncCategory(){ catHidden.value = TYPE_TO_CATEGORY[type.value] || 'その他'; }
    function recalcM3(){
      if(m3manual.checked){ return num(m3.value); }
      var l=Math.round(num(L.value)), w=Math.round(num(W.value)), h=Math.round(num(H.value));
      var v = (l>0 && w>0 && h>0) ? (l*w*h/1e9) : 0; // m3
      var rounded = Math.round(v*1000)/1000; // 3位
      m3.value = rounded ? rounded.toFixed(3) : '';
      return rounded;
    }
    function updateByM3Unit(){
      var v = num(m3.value);
      var mu= Math.round(num(m3u.value));
      if(v>0 && mu>0){ unit.value = String(Math.round(v*mu)); }
    }
    function updateM3UnitByUnit(){
      var v = num(m3.value);
      var u = Math.round(num(unit.value));
      if(v>0 && u>0){ m3u.value = String(Math.round(u/v)); }
    }
    function updateAmount(){
      var u=Math.round(num(unit.value)), q=Math.max(1,Math.round(num(qty.value)));
      qty.value = q;
      var v = Math.round(u*q);
      amt.value = String(v);
    }
    function toggleHeading(){
      var controls = [L,W,H,m3,m3u,unit,qty];
      var disabled = heading.checked;
      controls.forEach(function(el){ el.readOnly = disabled; el.disabled = disabled; if(disabled){ el.value=''; } });
      if(disabled){ amt.value=''; incl.checked=false; incl.disabled=true; } else { incl.disabled=false; }
    }
    function toggleM3Manual(){
      if(m3manual.checked){ m3.readOnly=false; }
      else{ m3.readOnly=true; recalcM3(); updateByM3Unit(); }
    }

    [L,W,H].forEach(function(el){ el.addEventListener('input', function(){ recalcM3(); updateByM3Unit(); updateAmount(); grandTotal(); });});
    m3.addEventListener('input', function(){ if(m3manual.checked){ updateByM3Unit(); updateAmount(); grandTotal(); } });
    m3u.addEventListener('input', function(){ updateByM3Unit(); updateAmount(); grandTotal(); });
    unit.addEventListener('input', function(){ updateM3UnitByUnit(); updateAmount(); grandTotal(); });
    qty.addEventListener('input', function(){ updateAmount(); grandTotal(); });
    type.addEventListener('change', function(){ syncCategory(); recalcM3(); updateByM3Unit(); updateAmount(); grandTotal(); });
    m3manual.addEventListener('change', function(){ toggleM3Manual(); recalcM3(); updateByM3Unit(); updateAmount(); grandTotal(); });
    heading.addEventListener('change', function(){ toggleHeading(); grandTotal(); });
    row.querySelector('.del').addEventListener('click', function(){ row.remove(); grandTotal(); });
    incl.addEventListener('change', function(){ grandTotal(); });

    syncCategory();
    toggleM3Manual();
    toggleHeading();
    recalcM3();
    updateByM3Unit();
    updateAmount();
  }

  function grandTotal(){
    var rows = document.querySelectorAll('.tr');
    var subtotal=0;
    rows.forEach(function(r){
      var incl = r.querySelector('.incl');
      var heading = r.querySelector('.heading');
      if(heading && heading.checked){ return; }
      if(incl && !incl.checked){ return; }
      var val = r.querySelector('[name="amount[]"]').value;
      subtotal += Math.round(num(val));
    });
    var tax = Math.round(subtotal * TAX_RATE);
    var total = subtotal + tax;
    var subEl=document.getElementById('subtotal');
    var taxEl=document.getElementById('tax');
    var totEl=document.getElementById('total');
    if(subEl) subEl.textContent = yenInt(subtotal);
    if(taxEl) taxEl.textContent = yenInt(tax);
    if(totEl) totEl.textContent = yenInt(total);
  }

  document.addEventListener('DOMContentLoaded', function(){
    var list = document.getElementById('item-list');
    var addBtn = document.getElementById('add-row');
    if(!list || !addBtn){ console.error('item-list / add-row が見つかりません'); return; }

    addBtn.addEventListener('click', function(){
      list.appendChild(newRow());
      grandTotal();
    });

    // 初期1行（見出し行追加の操作は行の「見出し」チェックで）
    list.appendChild(newRow());
    grandTotal();
  });
})();