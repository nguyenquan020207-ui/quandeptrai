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
