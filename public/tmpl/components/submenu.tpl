<ul class="nav nav-list">
  {foreach $menu as $m}
    {if $m.title!="url"}

      {if is_array($m.children)}
        <li>
          <a href="{if $m.url}{$site.URL}/{$m.url}{else}#{/if}"><i class="icon-{$m.icon}"></i> {$m.title}</a>
          {*component name='submenu.tpl' menu=$m.children*}
        {else}
        <li>
          <a href="{$site.URL}/{$m.url}"{if $m.active} class="active"{/if}><i class="icon-{$m.icon}"></i> {$m.title}</a>
        {/if}
      </li>
    {/if}
  {foreachelse}
    {* Sem menu *}
  {/foreach}
</ul>