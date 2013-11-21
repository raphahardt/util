#{** ------------------------------------------------------------------
 * skin.tpl
 * --------------------------------------------------------------------
 * É o esqueleto principal das páginas. Esta camada define a base para as páginas do
 * site, mas não define regras da página e nem contém nenhum layout. As regras gerais
 * da página devem ser definidas na próxima camada (site.tpl) e deve usar blocos
 * 'skin.[nomebloco]'.
 *}

<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="#{$site.lang}"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8" lang="#{$site.lang}"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9" lang="#{$site.lang}"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="#{$site.lang}"> <!--<![endif]-->
  <head>
    <meta charset="#{$site.charset}">
    #{block 'skin.head'}#{/block}
    #{block 'skin.head.javascript'}#{/block}
  </head>
  <body>
    #{block 'skin.body.header'}#{/block}
    #{block 'skin.body.content'}#{/block}
    #{block 'skin.body.footer'}#{/block}
    #{block 'skin.body.javascript'}#{/block}
  </body>
</html>