#{** ------------------------------------------------------------------
 * site.tpl
 * --------------------------------------------------------------------
 * É o esqueleto "secundário" das páginas. Esta camada contém algumas definições globais
 * para o funcionamento dos views, mas ainda não contém nenhum layout. O layout (template.tpl)
 * deve usar os blocos 'site.[nomebloco]' para alterar/substituir 
 *}

#{** ------------------------------------------------------------------
 * <head> do site.
 * Sua pagina deve usar 'page.[nome do bloco]'
 * @block site.head
 *}
#{block 'skin.head' append}
  #{* ... *}
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  #{block 'site.head'}
    <title>#{block 'page.title'}#{$site.title}#{/block}</title>

    <meta name="author" content="#{$site.owner}"/>
    <meta name="copyright" content="#{$site.copyright}"/>
    <meta name="keywords" content="#{$site.keywords}"/>
    <meta name="description" content="#{block 'page.description'}#{$site.description}#{/block}"/>
    
    <link rel="canonical" href="#{$site.fullURL}/#{$site.currentURL}"/>
    
    #{block 'page.icons'}
      #{foreach $view.icons as $icon}
        <link rel="#{$icon.type}"#{if $icon.sizes} sizes="#{$icon.sizes}"#{/if} href="#{$icon.file}">
      #{foreachelse}
        #{foreach $site.icons as $icon}
          <link rel="#{$icon.type}"#{if $icon.sizes} sizes="#{$icon.sizes}"#{/if} href="#{$icon.file}">
        #{foreachelse}
          <link rel="icon" href="#{$site.URL}/favicon.ico">
        #{/foreach}
      #{/foreach}
    #{/block}
  #{/block}
  #{* ... *}
#{/block}

#{** -----------------------------------------------------------------
 * Javascripts que ficam na <head> do site.
 * Use 'site.head.javascript' com 'append' para acrescentar outros scripts
 * @block site.head.javascript
 *}
#{block 'skin.head.javascript' append}
  #{* ... *}
  #{**
   * Variáveis globais do javascript.
   * Você deve definir no View com o método View::addJSVar()
   *}
  #{block 'site.head.javascript'}
    #{if $view.js_vars}
    <script>#{foreach $view.js_vars as $var}#{if $var@first}var #{$var.name}=#{$var.value}#{else},#{$var.name}=#{$var.value}#{/if}#{/foreach}</script>
    #{/if}
    #{**
     * Angular Loader
     * Serve para Angular carregar arquivos em qualquer ordem
     *}
    <script src="#{$site.URL}/public/js/core/angular/angular-loader.js"></script>
    #{**
     * $script Async Loader
     * Serve para carregar de forma asincrona os javascripts
     * @link https://github.com/ded/script.js
     *}
    <script src="#{$site.URL}/public/js/core/script.js"></script>
    
    #{**
     * Google Analytics.
     * Para retirar o bloco de alguma página, só abrir um bloco vazio de 'view.analytics'
     *}
    #{block 'view.analytics'}
      #{* antigo
      #{if $view.ga}<script>var _gaq=[['_setAccount','#{$view.ga}'],['_setDomainName', '#{$site.domain}'],['_setAllowLinker', true],['_trackPageview']];(function(d){ var g=d.createElement('script'),s=d.scripts[0];g.src='//www.google-analytics.com/ga.js';s.parentNode.insertBefore(g,s)}(document))</script>#{/if}*}
      #{if $view.ga}<script>$script("//www.google-analytics.com/ga.js",function(){var t=_gat._getTracker("#{$view.ga}");t._setDomainName("#{$site.domain}");t._trackPageview();});</script>#{/if}
    #{/block}
    
    #{**
     * Polyfills em javascript
     * Para retirar o bloco de alguma página, só abrir um bloco vazio de 'view.polyfills'
     * Para acrescentar mais polyfills, use o bloco com 'append'
     *}
    #{block 'view.polyfills'}
      <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
      <!--[if lt IE 9]>
        <script src="#{$site.URL}/min/js/polyfills/json2.js"></script>
        <script src="#{$site.URL}/min/js/polyfills/respond.min.js"></script>
        <script src="http://ie7-js.googlecode.com/svn/version/2.1(beta4)/IE9.js"></script>
      <![endif]-->
    #{/block}
  #{/block}
  #{* ... *}
#{/block}
  

#{** ------------------------------------------------------------------
 * <body> do site.
 *}
 
#{** -----------------------------------------------------------------
 * Conteudo fixo que fica no topo do site
 * Use 'site.body.header' com 'append' para acrescentar layout
 * @block site.body.header
 *}
#{block 'skin.body.header' append}
  #{* ... *}
  #{block 'site.body.header'}#{/block}
  #{* ... *}
#{/block}

#{** -----------------------------------------------------------------
 * Conteudo dinâmico que fica como conteúdo principal do site
 * Use 'site.body.content' com 'append' para acrescentar layout
 * @block site.body.content
 *}
#{block 'skin.body.content' append}
  #{* ... *}
  #{block 'site.body.content'}#{/block}
  #{* ... *}
#{/block}

#{** -----------------------------------------------------------------
 * Conteudo fixo que fica no rodapé do site
 * Use 'site.body.footer' com 'append' para acrescentar layout
 * @block site.body.footer
 *}
#{block 'skin.body.footer' append}
  #{* ... *}
  #{block 'site.body.footer'}#{/block}
  #{* ... *}
#{/block}


#{** -----------------------------------------------------------------
 * Javascripts que ficam na <body> do site.
 * Use 'site.body.javascript' com 'append' para acrescentar outros scripts
 *}
#{block 'skin.body.javascript' append}
  <script>
    $script.path('#{$site.URL}/min/js/app/');
    
    // jquery
    $script('http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js', 'jquery');
    
    $script.ready('jquery', function () {
      // angular
      $script([
        'http://code.angularjs.org/1.2.1/angular.min.js',
        'http://code.angularjs.org/1.2.1/angular-animate.min.js',
        'http://code.angularjs.org/1.2.1/i18n/angular-locale_#{$site.lang|lower}.js'
      ], 'angularjs');
      
      // bootstrap
      $script.ready('angularjs', function () {
        $script('#{$site.fullURL}/min/js/core/bootstrap/ng-bootstrap.js', 'bootstrap');
      });
    });
  </script>
  #{* ... *}
  #{block 'site.body.javascript'}#{/block}
  #{* ... *}
#{/block}  