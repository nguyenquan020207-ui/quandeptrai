<?php $themeBase=str_contains($_SERVER['SCRIPT_NAME']??'','/admin/')?'../':'';$themeScriptVersion=filemtime(__DIR__.'/../js/theme-switcher.js'); ?>
<div class="theme-switcher" data-theme-switcher>
 <button class="theme-switcher-trigger" type="button" data-theme-toggle aria-pressed="false" aria-label="Chuyển sang giao diện tối" title="Chuyển sang giao diện tối">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 1 0 0 18h1.1a2 2 0 0 0 1.5-3.3 1.8 1.8 0 0 1 1.4-3h1.1A4.9 4.9 0 0 0 22 9.8C22 6 17.5 3 12 3Z"/><circle cx="7.5" cy="10" r="1"/><circle cx="11" cy="7" r="1"/><circle cx="15.5" cy="7.7" r="1"/></svg>
  <span>Giao diện</span>
 </button>
</div>
<script src="<?=$themeBase?>js/theme-switcher.js?v=<?=$themeScriptVersion?>" defer></script>