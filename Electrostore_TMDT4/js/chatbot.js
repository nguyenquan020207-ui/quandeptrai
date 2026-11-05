(()=>{
  const root=document.querySelector('[data-chatbot]');
  if(!root)return;
  const panel=root.querySelector('[data-chat-panel]');
  const open=root.querySelector('[data-chat-open]');
  const close=root.querySelector('[data-chat-close]');
  const form=root.querySelector('[data-chat-form]');
  const input=root.querySelector('[data-chat-input]');
  const messages=root.querySelector('[data-chat-messages]');
  const suggestions=root.querySelector('[data-chat-suggestions]');
  const sendButton=form.querySelector('button[type="submit"]');
  const conversation=[];
  const setOpen=state=>{
    panel.hidden=!state;
    open.setAttribute('aria-expanded',String(state));
    if(state){
      const themePanel=document.querySelector('.theme-switcher-panel');
      const themeTrigger=document.querySelector('[data-theme-open]');
      if(themePanel)themePanel.hidden=true;
      if(themeTrigger)themeTrigger.setAttribute('aria-expanded','false');
      input.focus();
    }
  };
  open.addEventListener('click',()=>setOpen(panel.hidden));
  close.addEventListener('click',()=>setOpen(false));
  document.addEventListener('pointerdown',event=>{
    const target=event.target;
    if(!root.contains(target)&&!panel.hidden)setOpen(false);
    const hotline=document.querySelector('.hotline-contact');
    if(hotline?.open&&!hotline.contains(target))hotline.open=false;
  });
  document.addEventListener('keydown',event=>{
    if(event.key==='Escape'){
      if(!panel.hidden)setOpen(false);
      const hotline=document.querySelector('.hotline-contact');
      if(hotline?.open)hotline.open=false;
    }
  });
  const bubble=(text,who)=>{
    const node=document.createElement('p');
    node.className='chat-bubble '+(who||'bot-message');
    node.textContent=text;
    messages.append(node);
    messages.scrollTop=messages.scrollHeight;
    return node;
  };
  const addProduct=product=>{
    const card=document.createElement('article');
    card.className='chat-product-card';
    const image=document.createElement('img');
    image.className='chat-product-image';
    image.alt=product.name||'Sản phẩm';
    image.loading='lazy';
    image.src=product.image_url||'';
    image.addEventListener('error',()=>{image.hidden=true},{once:true});
    const details=document.createElement('div');
    details.className='chat-product-info';
    const title=document.createElement('b');
    title.textContent=product.name||'Sản phẩm';
    const meta=document.createElement('span');
    meta.textContent=[product.brand,product.category_name].filter(Boolean).join(' · ');
    const price=document.createElement('strong');
    price.textContent=new Intl.NumberFormat('vi-VN').format(Number(product.sale_price||0))+'₫';
    const stock=document.createElement('small');
    stock.textContent=Number(product.stock)>0?'Còn '+product.stock+' sản phẩm':'Tạm hết hàng';
    const description=document.createElement('p');
    description.textContent=product.description||'';
    const specs=document.createElement('small');
    specs.textContent=product.specs||'';
    details.append(title,meta,price,stock);
    if(description.textContent)details.append(description);
    if(specs.textContent)details.append(specs);
    const link=document.createElement('a');
    link.className='chat-product-link';
    link.textContent='Xem chi tiết';
    try{
      const endpoint=new URL(root.dataset.endpoint,window.location.href);
      const appBase=new URL('../',endpoint);
      link.href=new URL('index.php?page=chitiet&id='+encodeURIComponent(product.id),appBase).href;
    }catch(_){link.href='index.php?page=sanpham';}
    card.append(image,details,link);
    messages.append(card);
  };
  const send=async value=>{
    const message=(value||input.value).trim();
    if(!message||sendButton.disabled)return;
    input.value='';
    if(suggestions)suggestions.hidden=true;
    bubble(message,'user-message');
    conversation.push({role:'user',content:message});
    const pending=bubble('Để mình xem nhé…','bot-message pending-message');
    sendButton.disabled=true;
    input.disabled=true;
    try{
      const response=await fetch(root.dataset.endpoint,{
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json'},
        body:JSON.stringify({message,history:conversation.slice(-8)})
      });
      const result=await response.json();
      if(!response.ok)throw new Error(result.message||'Không gửi được câu hỏi.');
      pending.remove();
      const reply=result.reply||'Mình chưa nắm rõ ý bạn. Bạn nói thêm một chút nhé?';
      bubble(reply,'bot-message');
      conversation.push({role:'assistant',content:reply});
      (result.products||[]).forEach(addProduct);
      messages.scrollTop=messages.scrollHeight;
    }catch(error){
      pending.remove();
      const fallback='Mình đang gặp trục trặc khi tra cứu. Bạn thử lại sau một chút hoặc gọi hotline 1900 6868 nhé.';
      bubble(fallback,'bot-message');
      conversation.push({role:'assistant',content:fallback});
    }finally{
      sendButton.disabled=false;
      input.disabled=false;
      input.focus();
    }
  };
  form.addEventListener('submit',event=>{event.preventDefault();send();});
  if(suggestions)suggestions.addEventListener('click',event=>{
    const button=event.target.closest('button[data-chat-prompt]');
    if(button){input.value=button.dataset.chatPrompt;send(button.dataset.chatPrompt);}
  });
})();
