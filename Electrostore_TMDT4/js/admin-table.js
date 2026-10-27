document.querySelectorAll('.resource-table-panel .admin-table').forEach((table)=>{
 const wrap=table.closest('.admin-table-wrap');if(!wrap)return;
 const toolbar=document.createElement('div');toolbar.className='resource-toolbar';
 const search=document.createElement('input');search.type='search';search.placeholder='Tìm trong bảng';search.setAttribute('aria-label','Tìm trong bảng');
 const picker=document.createElement('details');picker.className='column-picker';const summary=document.createElement('summary');summary.textContent='▥ Cột';picker.append(summary);const options=document.createElement('div');options.className='column-picker-options';picker.append(options);toolbar.append(search,picker);wrap.before(toolbar);
 const head=table.tHead?.rows[0];if(!head)return;
 const attendanceTable=table.classList.contains('attendance-admin-table');if(attendanceTable)picker.remove();
 let master=null;
 if(!attendanceTable){
  const th=document.createElement('th');th.className='selection-column';master=document.createElement('input');master.type='checkbox';master.setAttribute('aria-label','Chọn tất cả dòng');th.append(master);head.prepend(th);
  table.querySelectorAll('tbody tr').forEach(row=>{if(row.querySelector('.empty-cell'))return;const td=row.insertCell(0);td.className='selection-column';const cb=document.createElement('input');cb.type='checkbox';cb.setAttribute('aria-label','Chọn dòng');td.append(cb);cb.addEventListener('change',sync)});
  master.addEventListener('change',()=>{table.querySelectorAll('tbody .selection-column input[type=checkbox]').forEach(cb=>cb.checked=master.checked);sync()});
 }
 [...head.cells].forEach((cell,index)=>cell.dataset.columnKey=String(index));
 [...table.rows].slice(1).forEach(row=>[...row.cells].forEach((cell,index)=>cell.dataset.columnKey=String(index)));
 function sync(){if(!master)return;const boxes=[...table.querySelectorAll('tbody .selection-column input[type=checkbox]')],selected=boxes.filter(cb=>cb.checked).length;toolbar.dataset.selected=selected?`${selected} dòng đã chọn`:'';master.checked=selected>0&&selected===boxes.length;master.indeterminate=selected>0&&!master.checked}
 const dataColumns=[...head.cells].filter(cell=>!cell.classList.contains('selection-column'));
 dataColumns.forEach((cell)=>{
  const key=cell.dataset.columnKey,label=(cell.textContent||'Cột').trim(),item=document.createElement('label');item.className='column-option';item.dataset.columnKey=key;
  const handle=document.createElement('span');handle.className='column-drag-handle';handle.textContent='⠿';handle.title='Kéo để sắp xếp cột';handle.setAttribute('aria-hidden','true');
  const cb=document.createElement('input');cb.type='checkbox';cb.checked=true;cb.setAttribute('aria-label',`Hiện cột ${label}`);cb.addEventListener('change',()=>[...table.rows].forEach(row=>{const target=[...row.cells].find(cell=>cell.dataset.columnKey===key);if(target)target.hidden=!cb.checked}));item.append(handle,cb,document.createTextNode(label));options.append(item);
  handle.addEventListener('pointerdown',(event)=>{if(event.button!==undefined&&event.button!==0)return;event.preventDefault();event.stopPropagation();item.classList.add('is-dragging');options._draggedItem=item}); });
 function applyOrder(){const keys=[...options.querySelectorAll('[data-column-key]')].map(item=>item.dataset.columnKey);[...table.rows].forEach(row=>{const selection=[...row.cells].find(cell=>cell.classList.contains('selection-column'));const mapped=new Map([...row.cells].filter(cell=>!cell.classList.contains('selection-column')).map(cell=>[cell.dataset.columnKey,cell]));if(selection)row.append(selection);keys.forEach(key=>{const cell=mapped.get(key);if(cell)row.append(cell)})})}
options.addEventListener('pointermove',(event)=>{const dragged=options._draggedItem;if(!dragged)return;event.preventDefault();const target=[...options.querySelectorAll('.column-option')].find(item=>item!==dragged&&event.clientY>=item.getBoundingClientRect().top&&event.clientY<=item.getBoundingClientRect().bottom);if(!target)return;const box=target.getBoundingClientRect(),after=event.clientY>box.top+box.height/2;options.insertBefore(dragged,after?target.nextSibling:target)});
 const finishColumnDrag=()=>{const dragged=options._draggedItem;if(!dragged)return;dragged.classList.remove('is-dragging');delete options._draggedItem;applyOrder()};
 document.addEventListener('pointerup',finishColumnDrag);document.addEventListener('pointercancel',finishColumnDrag);
 search.addEventListener('input',()=>{const value=search.value.trim().toLocaleLowerCase('vi');table.querySelectorAll('tbody tr').forEach(row=>{if(!row.querySelector('.empty-cell'))row.hidden=!row.textContent.toLocaleLowerCase('vi').includes(value)})});
});
document.querySelectorAll('.resource-table-panel .admin-table').forEach((table)=>{const head=table.tHead?.rows[0];if(!head)return;
[...head.cells].slice(1).forEach((cell)=>{cell.classList.add('sortable-column');cell.title='Sắp xếp theo cột';cell.addEventListener('click',(event)=>{if(event.target.closest('input,button,a,form,select'))return;const col=cell.cellIndex;const body=table.tBodies[0];if(!body)return;const rows=[...body.rows].filter(row=>!row.querySelector('.empty-cell'));const asc=cell.dataset.sort!=='asc';cell.dataset.sort=asc?'asc':'desc';rows.sort((a,b)=>{const x=(a.cells[col]?.innerText||'').trim(),y=(b.cells[col]?.innerText||'').trim();const nx=Number(x.replace(/[^0-9,.-]/g,'').replace(/\./g,'').replace(',','.')),ny=Number(y.replace(/[^0-9,.-]/g,'').replace(/\./g,'').replace(',','.'));const cmp=Number.isFinite(nx)&&Number.isFinite(ny)?nx-ny:x.localeCompare(y,'vi',{numeric:true,sensitivity:'base'});return asc?cmp:-cmp});rows.forEach(row=>body.append(row))})});
document.querySelectorAll('.product-row-menu').forEach((menu)=>{const popover=menu.querySelector('.product-menu-popover');const summary=menu.querySelector('summary');if(!popover||!summary)return;const place=()=>{if(!menu.open)return;const box=summary.getBoundingClientRect();popover.style.position='fixed';popover.style.right=Math.max(10,window.innerWidth-box.right)+'px';popover.style.left='auto';popover.style.bottom='auto';popover.style.top=Math.min(box.bottom+5,window.innerHeight-popover.offsetHeight-10)+'px'};menu.addEventListener('toggle',place);window.addEventListener('resize',place);window.addEventListener('scroll',place,true)});

document.addEventListener('click',(event)=>{document.querySelectorAll('.product-row-menu[open]').forEach(menu=>{if(!menu.contains(event.target))menu.open=false})});

document.querySelectorAll('.product-action-trigger').forEach((button)=>button.addEventListener('click',()=>{
 const dialog=document.getElementById('product-action-dialog');
 const form=document.getElementById('product-action-form');
 if(!dialog||!form)return;
 const price=button.dataset.kind==='price';
 form.querySelector('[name=action]').value=price?'adjust_product_price':'adjust_product_stock';
 form.querySelector('[name=id]').value=button.dataset.id||'';
 document.getElementById('product-action-title').textContent=(price?'Điều chỉnh giá':'Điều chỉnh hàng tồn kho');
 document.getElementById('product-action-description').textContent=button.dataset.name||'';
 form.querySelectorAll('.dialog-price-field').forEach(label=>{label.hidden=!price;label.querySelector('input').disabled=!price;label.querySelector('input').required=price});
 form.querySelectorAll('.dialog-stock-field').forEach(label=>{label.hidden=price;label.querySelector('input').disabled=price;label.querySelector('input').required=!price});
 form.elements.list_price.value=button.dataset.listPrice||'';
 form.elements.sale_price.value=button.dataset.salePrice||'';
 form.elements.stock.value=button.dataset.stock||'0';
 const rowMenu=button.closest('.product-row-menu');if(rowMenu)rowMenu.open=false;
 dialog.showModal();
}));
});
document.querySelector('[data-close-product-dialog]')?.addEventListener('click',()=>document.getElementById('product-action-dialog')?.close());
document.getElementById('product-action-dialog')?.addEventListener('click',(event)=>{if(event.target===event.currentTarget)event.currentTarget.close()});

document.querySelectorAll('form[data-inline-toggle]').forEach((form)=>{
 form.addEventListener('submit',async(event)=>{
  event.preventDefault();
  const button=form.querySelector('button'); if(!button||button.disabled)return;
  const previousText=button.textContent; button.disabled=true;
  try{
   const response=await fetch(window.location.href,{method:'POST',body:new FormData(form),headers:{'X-Requested-With':'XMLHttpRequest','Accept':'application/json'}});
   const result=await response.json();
   if(!response.ok||!result.ok)throw new Error(result.message||'Không thể cập nhật trạng thái.');
   const active=Number(result.active)===1;
   form.elements.namedItem('active').value=active?'0':'1';
   button.textContent=active?'Ẩn':'Hiện';
   const badge=form.closest('tr')?.querySelector('.admin-status');
   if(badge){badge.classList.toggle('active',active);badge.classList.toggle('inactive',!active);const activity=form.dataset.statusLabel==='activity';badge.textContent=active?(activity?'Đang hoạt động':'Đang hiển thị'):(activity?'Ngừng hoạt động':'Đã ẩn')}
   const brandIcon=form.closest('tr')?.querySelector('.brand-visibility-icon');if(brandIcon){brandIcon.classList.toggle('is-visible',active);brandIcon.classList.toggle('is-hidden',!active);brandIcon.title=active?'Đang hiển thị':'Đang ẩn';brandIcon.textContent=active?'✓':'×'}
  }catch(error){button.textContent=previousText}
  finally{button.disabled=false}
 });
});

document.querySelectorAll('[data-order-group]').forEach((select)=>{
 const table=select.closest('.orders-table-panel')?.querySelector('.orders-admin-table'); if(!table)return;
 select.addEventListener('change',()=>{
  const body=table.tBodies[0];if(!body)return;
  const rows=[...body.rows].filter(row=>!row.querySelector('.empty-cell'));
  const key=select.value;
  if(key)rows.sort((a,b)=>(a.dataset['order'+key[0].toUpperCase()+key.slice(1)]||'').localeCompare(b.dataset['order'+key[0].toUpperCase()+key.slice(1)]||'','vi',{numeric:true,sensitivity:'base'}));
  rows.forEach(row=>body.append(row));
 });
});
document.querySelectorAll('.order-row-menu').forEach((menu)=>{
 const popover=menu.querySelector('.order-menu-popover');const summary=menu.querySelector('summary');if(!popover||!summary)return;
 const place=()=>{if(!menu.open)return;const box=summary.getBoundingClientRect();popover.style.position='fixed';popover.style.right=Math.max(10,window.innerWidth-box.right)+'px';popover.style.left='auto';popover.style.top=Math.min(box.bottom+5,window.innerHeight-popover.offsetHeight-10)+'px'};
 menu.addEventListener('toggle',place);window.addEventListener('resize',place);window.addEventListener('scroll',place,true);
});
document.addEventListener('click',(event)=>{document.querySelectorAll('.order-row-menu[open]').forEach(menu=>{if(!menu.contains(event.target))menu.open=false})});

document.querySelectorAll('.category-csv-input').forEach((input)=>input.addEventListener('change',()=>{if(input.files?.length)input.form.requestSubmit()}));
