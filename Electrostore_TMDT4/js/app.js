document.addEventListener('DOMContentLoaded',()=>{
  const headerSearch=document.querySelector('.site-header .search input[name="q"]');
  const productsNav=document.querySelector('.topbar-nav a[href*="page=sanpham"]');
  if(headerSearch&&productsNav)productsNav.addEventListener('click',()=>{
    const term=headerSearch.value.trim();
    const target=new URL(productsNav.href,window.location.href);
    if(term)target.searchParams.set('q',term);else target.searchParams.delete('q');
    productsNav.href=target.toString();
  });
  document.querySelectorAll('form[data-confirm]').forEach(form=>form.addEventListener('submit',event=>{if(form.dataset.confirmed==='true'){delete form.dataset.confirmed;return}event.preventDefault();openFormConfirmation(form,event.submitter)}));
  document.addEventListener('click',event=>{const button=event.target.closest('[data-toggle-password]');if(!button)return;const input=document.getElementById(button.dataset.togglePassword);if(!input)return;const visible=input.type==='password';input.type=visible?'text':'password';button.setAttribute('aria-label',visible?'Ẩn mật khẩu':'Hiện mật khẩu');button.title=visible?'Ẩn mật khẩu':'Hiện mật khẩu';button.innerHTML=visible?'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8"></path><path d="M9.9 5.2A10.7 10.7 0 0 1 12 5c6.5 0 10 7 10 7a17 17 0 0 1-3.1 3.9M6.2 6.2C3.5 8 2 12 2 12s3.5 7 10 7a10 10 0 0 0 3-.4"></path></svg>':'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>'});
  document.addEventListener('submit',async event=>{const form=event.target.closest('form[data-cart-add]');if(!form)return;event.preventDefault();const button=form.querySelector('button[type="submit"],button:not([type])');if(button){button.disabled=true;button.dataset.oldText=button.textContent;button.textContent='Đang thêm...'}try{const response=await fetch(form.action,{method:'POST',body:new FormData(form),headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});const data=await response.json();if(!response.ok||!data.ok)throw new Error();document.querySelectorAll('.count').forEach(item=>item.textContent=data.cart_count);showToast('Đã thêm sản phẩm vào giỏ hàng.')}catch(error){showToast('Không thể thêm sản phẩm. Hãy tải lại trang và thử lại.')}finally{if(button){button.disabled=false;button.textContent=button.dataset.oldText||'Thêm vào giỏ'}}});
});
function showToast(message){let toast=document.getElementById('app-toast');if(!toast){toast=document.createElement('div');toast.id='app-toast';toast.className='app-toast';toast.setAttribute('role','status');toast.setAttribute('aria-live','polite');document.body.appendChild(toast)}toast.textContent=message;toast.classList.add('show');clearTimeout(window.electroToastTimer);window.electroToastTimer=setTimeout(()=>toast.classList.remove('show'),2600)}
function openFormConfirmation(form,submitter){
  let dialog=document.getElementById('app-confirm-dialog');
  if(!dialog){dialog=document.createElement('dialog');dialog.id='app-confirm-dialog';dialog.className='app-confirm-dialog';dialog.setAttribute('aria-labelledby','app-confirm-title');dialog.innerHTML='<div class="app-confirm-mark" aria-hidden="true">!</div><div class="app-confirm-copy"><h2 id="app-confirm-title"></h2><p class="app-confirm-message"></p></div><div class="app-confirm-actions"><button type="button" class="app-confirm-cancel">Quay lại</button><button type="button" class="app-confirm-accept"></button></div>';document.body.appendChild(dialog);dialog.addEventListener('click',event=>{if(event.target===dialog)dialog.close()})}
  dialog.querySelector('#app-confirm-title').textContent=form.dataset.confirmTitle||'Xác nhận thao tác';
  dialog.querySelector('.app-confirm-message').textContent=form.dataset.confirm||'Bạn có chắc chắn muốn tiếp tục?';
  dialog.querySelector('.app-confirm-accept').textContent=form.dataset.confirmAccept||'Xác nhận';
  dialog.querySelector('.app-confirm-cancel').onclick=()=>dialog.close();
  dialog.querySelector('.app-confirm-accept').onclick=()=>{dialog.close();form.dataset.confirmed='true';if(submitter&&form.contains(submitter))form.requestSubmit(submitter);else form.requestSubmit()};
  dialog.showModal();dialog.querySelector('.app-confirm-cancel').focus();
}
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('.cart-update-form').forEach(form=>form.addEventListener('click',event=>{
    const step=event.target.closest('[data-quantity-step]');
    if(!step||step.disabled)return;
    const control=step.closest('.cart-quantity');
    const input=control&&control.querySelector('[data-cart-quantity]');
    if(!input)return;
    const min=Number(input.min)||1,max=Number(input.max)||99,current=Number(input.value)||min,delta=Number(step.dataset.quantityStep)||0;
    input.value=String(Math.max(min,Math.min(max,current+delta)));
    control.querySelector('[data-quantity-step="-1"]').disabled=Number(input.value)<=min;
    control.querySelector('[data-quantity-step="1"]').disabled=Number(input.value)>=max;
    input.focus();
    clearTimeout(window.electroCartUpdateTimer);
    window.electroCartUpdateTimer=setTimeout(()=>form.requestSubmit(),700);
  }));
  document.querySelectorAll('.cart-update-form [data-cart-quantity]').forEach(input=>input.addEventListener('input',()=>{
    const control=input.closest('.cart-quantity');if(!control)return;
    const min=Number(input.min)||1,max=Number(input.max)||99,value=Number(input.value)||min;
    control.querySelector('[data-quantity-step="-1"]').disabled=value<=min;
    control.querySelector('[data-quantity-step="1"]').disabled=value>=max;
  }));
  document.querySelectorAll('.cart-update-form [data-cart-quantity]').forEach(input=>input.addEventListener('change',()=>{
    if(input.form&&input.checkValidity())input.form.requestSubmit();
  }));
});
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('form[data-password-match]').forEach(form=>{
    const password=form.querySelector('[name="password"]');
    const confirmation=form.querySelector('[name="confirm_password"]');
    if(!password||!confirmation)return;
    const validate=()=>confirmation.setCustomValidity(confirmation.value&&confirmation.value!==password.value?'Mật khẩu nhập lại chưa khớp.':'');
    password.addEventListener('input',validate);
    confirmation.addEventListener('input',validate);
    form.addEventListener('submit',event=>{validate();if(!form.reportValidity())event.preventDefault()});
  });
});
document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('form[data-login-form]').forEach(form=>form.addEventListener('submit',async event=>{
    event.preventDefault();
    const submitButton=event.submitter||form.querySelector('button[type="submit"]');
    const payload=new FormData(form);
    if(submitButton){submitButton.disabled=true;submitButton.dataset.originalText=submitButton.textContent;submitButton.textContent='Đang đăng nhập...'}
    try{
      const response=await fetch(form.action||location.href,{method:'POST',body:payload,headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
      const result=await response.json();
      if(!response.ok||!result.ok){showToast(result.message||'Email hoặc mật khẩu chưa đúng.');return}
      location.href=result.redirect||'index.php';
    }catch(error){showToast('Chưa thể kết nối. Hãy thử lại sau.')}
    finally{if(submitButton){submitButton.disabled=false;submitButton.textContent=submitButton.dataset.originalText||'Đăng nhập'}}
  }));
});

// Giỏ hàng nhanh dạng hộp thoại ở giữa màn hình.
(()=>{
  const modal=document.getElementById('cart-modal');
  if(!modal)return;
  const body=modal.querySelector('.cart-modal-body');
  const dialog=modal.querySelector('.cart-modal-dialog');
  let returnFocus=null;
  const setBusy=(busy)=>{modal.setAttribute('aria-busy',busy?'true':'false')};
  let pendingMutation=null,mutationRunning=false;
  const formatMoney=value=>new Intl.NumberFormat('vi-VN').format(Math.max(0,Math.round(value)))+'₫';
  const readMoney=node=>Number((node?.textContent||'').replace(/[^0-9]/g,''))||0;
  const setCartCount=(cart,count)=>{
    const heading=cart.querySelector('.cart-page-heading h1 span');if(heading)heading.textContent=`(${count})`;
    document.querySelectorAll('.floating-cart .count').forEach(node=>node.textContent=String(count));
    document.querySelectorAll('.floating-cart').forEach(node=>node.setAttribute('aria-label',`Mở giỏ hàng, ${count} sản phẩm`));
  };
  const updateCartPreview=(cart)=>{
    if(!cart)return;const cards=[...cart.querySelectorAll('.cart-product-card')];let subtotal=0,count=0;
    cards.forEach(card=>{
      const input=card.querySelector('[data-cart-quantity]');if(!input)return;
      const quantity=Math.max(Number(input.min)||1,Math.min(Number(input.max)||99,Number(input.value)||Number(input.min)||1));
      input.value=String(quantity);count+=quantity;
      const line=readMoney(card.querySelector('.cart-product-price strong'))*quantity;subtotal+=line;
      const total=card.querySelector('.cart-line-total');if(total)total.textContent=formatMoney(line);
      const control=input.closest('.cart-quantity');
      if(control){const minus=control.querySelector('[data-quantity-step="-1"]'),plus=control.querySelector('[data-quantity-step="1"]');if(minus)minus.disabled=quantity<=(Number(input.min)||1);if(plus)plus.disabled=quantity>=(Number(input.max)||99)}
    });
    const shipping=subtotal>=5000000||subtotal===0?0:100000,total=subtotal+shipping;
    const lines=cart.querySelector('.cart-summary-lines'),summaryRows=lines?[...lines.querySelectorAll(':scope > p')]:[];
    const subtotalValue=summaryRows.find(row=>!row.classList.contains('cart-summary-total'))?.querySelector('strong');
    const shippingValue=summaryRows.find(row=>row.querySelector('span')?.textContent.includes('Phí vận chuyển'))?.querySelector('strong');
    const totalValue=cart.querySelector('.cart-summary-total strong');
    if(subtotalValue)subtotalValue.textContent=formatMoney(subtotal);
    if(shippingValue)shippingValue.textContent=shipping?formatMoney(shipping):'Miễn phí';
    if(totalValue)totalValue.textContent=formatMoney(total);
    const free=5000000,copy=cart.querySelector('.cart-shipping-copy > span:first-child');
    if(copy)copy.innerHTML=subtotal>=free?'Đơn hàng của bạn được miễn phí vận chuyển.':`Mua thêm <strong>${formatMoney(free-subtotal)}</strong> để được <strong>miễn phí vận chuyển</strong>.`;
    const shippingAmount=cart.querySelector('.cart-shipping-amount');if(shippingAmount)shippingAmount.textContent=shipping?`Phí vận chuyển ${formatMoney(shipping)}`:'Đã đạt ưu đãi vận chuyển';
    const progress=cart.querySelector('.cart-progress-track > span');if(progress)progress.style.width=`${Math.min(100,subtotal/free*100)}%`;
    setCartCount(cart,count);
  };
  const processMutations=async()=>{
    if(mutationRunning)return;mutationRunning=true;setBusy(true);
    while(pendingMutation){
      const job=pendingMutation;pendingMutation=null;
      try{const response=await fetch(job.url,{method:'POST',body:job.payload,headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});const html=await response.text();if(!response.ok)throw new Error();if(!pendingMutation)renderCart(html)}
      catch(error){showToast('Chưa cập nhật được giỏ hàng. Vui lòng thử lại.');if(!pendingMutation)break}
    }
    mutationRunning=false;setBusy(false);if(pendingMutation)processMutations();
  };
  const renderCart=(html)=>{
    const parsed=new DOMParser().parseFromString(html,'text/html');
    const cart=parsed.querySelector('.cart-page');
    if(!cart)throw new Error('Không tải được nội dung giỏ hàng.');
    body.replaceChildren(cart);
    const countText=cart.querySelector('.cart-page-heading h1 span')?.textContent||'(0)';
    const count=(countText.match(/\d+/)||['0'])[0];
    document.querySelectorAll('.floating-cart .count').forEach(node=>node.textContent=count);
    document.querySelectorAll('.floating-cart').forEach(node=>node.setAttribute('aria-label',`Mở giỏ hàng, ${count} sản phẩm`));
  };
  const loadCart=async()=>{
    dialog.setAttribute('aria-label','Giỏ hàng');modal.querySelector('.cart-modal-close')?.setAttribute('aria-label','Đóng giỏ hàng');
    const link=document.querySelector('.floating-cart');
    const url=new URL(link?.href||'index.php?page=giohang',location.href);
    url.searchParams.set('page','giohang');url.searchParams.set('fragment','1');
    setBusy(true);body.innerHTML='<p class="cart-modal-loading">Đang tải giỏ hàng…</p>';
    try{const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});if(!response.ok)throw new Error();renderCart(await response.text())}
    catch(error){body.innerHTML='<div class="cart-modal-error">Chưa tải được giỏ hàng. <a href="index.php?page=giohang">Mở trang giỏ hàng</a></div>'}
    finally{setBusy(false)}
  };
  const renderCheckout=(html)=>{
    const parsed=new DOMParser().parseFromString(html,'text/html');
    const content=parsed.querySelector('.checkout-modal-content');
    if(!content)throw new Error('Không tải được biểu mẫu thanh toán.');
    body.replaceChildren(content);
    if(content.classList.contains('checkout-result')){document.querySelectorAll('.floating-cart .count').forEach(node=>node.textContent='0');document.querySelectorAll('.floating-cart').forEach(node=>node.setAttribute('aria-label','Mở giỏ hàng, 0 sản phẩm'))}
  };
  const loadCheckout=async(link)=>{
    dialog.setAttribute('aria-label','Thanh toán');modal.querySelector('.cart-modal-close')?.setAttribute('aria-label','Đóng thanh toán');
    const url=new URL(link?.href||'index.php?page=thanhtoan',location.href);url.searchParams.set('page','thanhtoan');url.searchParams.set('fragment','1');
    setBusy(true);body.innerHTML='<p class="cart-modal-loading">Đang tải thanh toán…</p>';
    try{const response=await fetch(url,{headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}});if(!response.ok)throw new Error();renderCheckout(await response.text())}
    catch(error){body.innerHTML='<div class="cart-modal-error">Chưa tải được thanh toán. <a href="index.php?page=thanhtoan">Mở trang thanh toán</a></div>'}
    finally{setBusy(false)}
  };
  const open=()=>{if(!modal.hidden)return;returnFocus=document.activeElement;modal.hidden=false;document.body.classList.add('cart-modal-open');loadCart();requestAnimationFrame(()=>modal.querySelector('.cart-modal-close')?.focus())};
  const close=()=>{if(modal.hidden)return;modal.hidden=true;document.body.classList.remove('cart-modal-open');if(returnFocus instanceof HTMLElement)returnFocus.focus()};
  document.addEventListener('click',event=>{
    const checkoutLink=event.target.closest('a[href*="page=thanhtoan"]');
    if(checkoutLink){event.preventDefault();if(modal.hidden){returnFocus=document.activeElement;modal.hidden=false;document.body.classList.add('cart-modal-open')}loadCheckout(checkoutLink);requestAnimationFrame(()=>modal.querySelector('.cart-modal-close')?.focus());return}
    const opener=event.target.closest('.floating-cart');
    if(opener){event.preventDefault();open();return}
    if(event.target.closest('[data-cart-modal-close]')){event.preventDefault();close();return}
    const step=event.target.closest('#cart-modal [data-quantity-step]');
    if(!step||step.disabled)return;
    event.preventDefault();const form=step.closest('form.cart-update-form'),control=step.closest('.cart-quantity'),input=control?.querySelector('[data-cart-quantity]');
    if(!form||!input)return;
    const min=Number(input.min)||1,max=Number(input.max)||99,value=Number(input.value)||min,delta=Number(step.dataset.quantityStep)||0;
    input.value=String(Math.max(min,Math.min(max,value+delta)));
    updateCartPreview(body.querySelector('.cart-page'));
    form.requestSubmit();
  });
  document.addEventListener('input',event=>{
    const input=event.target.closest('#cart-modal [data-cart-quantity]');if(!input)return;
    const control=input.closest('.cart-quantity'),value=Number(input.value)||Number(input.min)||1,min=Number(input.min)||1,max=Number(input.max)||99;
    const minus=control?.querySelector('[data-quantity-step="-1"]'),plus=control?.querySelector('[data-quantity-step="1"]');
    if(minus)minus.disabled=value<=min;if(plus)plus.disabled=value>=max;
    updateCartPreview(body.querySelector('.cart-page'));
  });
  document.addEventListener('change',event=>{const input=event.target.closest('#cart-modal [data-cart-quantity]');if(input?.form&&input.checkValidity())input.form.requestSubmit()});
  document.addEventListener('submit',event=>{
    const checkoutForm=event.target.closest('#cart-modal form.checkout-modal-form');
    if(checkoutForm){event.preventDefault();if(!checkoutForm.reportValidity())return;setBusy(true);const submit=checkoutForm.querySelector('button[type="submit"]');if(submit)submit.disabled=true;fetch(checkoutForm.action,{method:'POST',body:new FormData(checkoutForm),headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html'}}).then(async response=>{if(!response.ok)throw new Error();renderCheckout(await response.text())}).catch(()=>showToast('Chưa thể đặt hàng. Vui lòng thử lại.')).finally(()=>setBusy(false));return}
    const form=event.target.closest('#cart-modal form[action*="page=capnhat_don"]');if(!form)return;
    event.preventDefault();const submitter=event.submitter;
    if(form.dataset.confirm&&form.dataset.confirmed!=='true'){openFormConfirmation(form,submitter);return}
    delete form.dataset.confirmed;
    let payload;try{payload=new FormData(form,submitter||undefined)}catch(error){payload=new FormData(form);if(submitter?.name)payload.append(submitter.name,submitter.value)}
    pendingMutation={url:form.action||'index.php?page=capnhat_don',payload};processMutations();
  });
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!modal.hidden){event.preventDefault();close()}});
  if(new URLSearchParams(location.search).get('checkout')==='1'||modal.dataset.openCheckout==='1'){
    returnFocus=document.querySelector('.floating-cart');modal.hidden=false;document.body.classList.add('cart-modal-open');loadCheckout();
  }
})();
