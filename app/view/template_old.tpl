#{block 'site.head'}
  <title>#{block 'page.title'}#{$site.title}#{/block}</title>

  <meta name="author" content="#{$site.owner}"/>
  <meta name="copyright" content="#{$site.copyright}"/>
  <meta name="keywords" content="#{$site.keywords}"/>
  <meta name="description" content="#{block 'page.description'}#{$site.description}#{/block}"/>

  #{block 'page.facebook_headers'}
  <meta property="og:title" content="#{block 'page.title'}#{$site.title}#{/block}"/>
  <meta property="og:type" content="object"/>
  <meta property="og:url" content="#{if $view.fullURL}#{$view.fullURL}#{else}#{$site.fullURL}#{/if}"/>
  <meta property="og:site_name" content="#{$site.title}"/>
  <meta property="og:description" content="#{block 'page.description'}#{$site.description}#{/block}"/>
  #{/block}
  <meta property="fb:app_id" content="#{$view.facebook_id}"/>
  
  <link rel="canonical" href="#{$site.fullURL}/#{$view.url}"/>

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
  
  <link href='http://fonts.googleapis.com/css?family=Comfortaa:400,700' rel='stylesheet' type='text/css'>
  #{*<link href='http://fonts.googleapis.com/css?family=Raleway:400,700' rel='stylesheet' type='text/css'>*}
  #{*<link href='http://fonts.googleapis.com/css?family=Open+Sans:400italic,400,700' rel='stylesheet' type='text/css'>*}
  #{*<link href='http://fonts.googleapis.com/css?family=Ubuntu:400,700,400italic' rel='stylesheet' type='text/css'>*}

  <link rel="stylesheet" type="text/css" href="#{$site.fullURL}/public/css/app.css">
  
  #{*<link rel="stylesheet" type="text/css" href="#{$site.fullURL}www/css/font-awesome.css">
  <link rel="stylesheet" type="text/css" href="#{$site.fullURL}min/?g=styles">
  <link rel="stylesheet" type="text/css" href="#{$site.fullURL}www/css/main.css">

  <script src="#{$site.fullURL}min/?g=essentials"></script>*}
  #{if $view.js_vars}
  <script>
    #{foreach $view.js_vars as $var}#{if $var@first}var #{$var.name}=#{$var.value}#{else},#{$var.name}=#{$var.value}#{/if}#{/foreach}
  </script>
  #{/if}
  
  <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!--[if lt IE 9]>
    <script src="#{$site.URL}/public/js/polyfills/json2.js"></script>
  <![endif]-->

  <!-- Analytics -->
  <script>var _gaq=[['_setAccount','#{$site.ga}'],['_setDomainName', '#{$site.domain}'],['_setAllowLinker', true],['_trackPageview']];(function(d){ var g=d.createElement('script'),s=d.scripts[0];g.src='//www.google-analytics.com/ga.js';s.parentNode.insertBefore(g,s)}(document))</script>
#{/block}
#{block 'site.header'}
  <div id="fb-root"></div>
  #{block 'page.header'}
  <header class="cn-header">
    <div class="container cn-headerbg cn-headerbg1">
      <hgroup>
        <h1><a href="#{$site.fullURL}">#{$site.title}</a></h1>
        <h2>#{$site.subtitle}</h2>
      </hgroup>
      #{block 'ad.728'}
      <!-- Propaganda! -->
      <div class="cn-ad cn-ad-fullbanner">
        <script><!--
        google_ad_client = "ca-pub-3544866933122847";
        /* topo todas paginas */
        google_ad_slot = "4678033558";
        google_ad_width = 728;
        google_ad_height = 90;
        //-->
        </script>
        <script type="text/javascript"
        src="//pagead2.googlesyndication.com/pagead/show_ads.js">
        </script>
      </div>
      #{/block}
    </div>
    <nav class="cn-menu">
      <div class="container">
        <ul>
          <li><a href="#{$site.fullURL}/menu1">Menu1</a></li>
          <li><a href="#{$site.fullURL}/menu2">Menu2</a></li>
          <li><a href="#{$site.fullURL}/menu3">Menu3</a></li>
        </ul>
      </div>
    </nav>
  </header>
  #{/block}
  
  #{block 'page.breadcrumb'}
    #{if $view.breadcrumb}
      <div class="container">
        <ul class="breadcrumb">
        #{foreach $view.breadcrumb as $bread}
          #{if $bread@last}
            <li class="active">#{$bread.title}</li>
          #{else}
            <li><a href="#{$site.fullURL}/#{$bread.url}">#{$bread.title}</a> <span class="divider">/</span></li>
          #{/if}
        #{/foreach}
        </ul>
      </div>
    #{/if}
  #{/block}
#{/block}
#{block 'site.body'}
  <div class="container">
  #{block 'page.contents'}
  #{/block}
  </div>
#{/block}
#{block 'site.footer'}
  #{block 'page.footer'}
  <div class="cn-footer-series" id="cn-footer-series">
    <h2 class="hide">Mais</h2>
    <div class="container">
      <ul>
      #{foreach $footerSeries as $serie}
        <li>
          <a href="#{$site.URL}series/#{$serie.key}">
            <img src="#{$site.URL}images/series/#{$serie.keyImage}/arq.jpg" />
            <span class="cn-legend">#{$serie.nome}</span>
          </a>
        </li>
      #{/foreach}
        <li class="more">
          <a href="#{$site.URL}series" title="Mais séries"><i class="icon-plus"></i><span class="hide">Mais</span></a>
        </li>
      </ul>
      <div class="cn-footer-series-left"></div>
      <div class="cn-footer-series-right"></div>
    </div>
  </div>
  <footer class="cn-footer">
    <div class="container">
      <div class="row">
        <div class="span4">
          gfdg
        </div>
        <div class="span4">
          gfd
        </div>
        <div class="span4">
          jjhuh
        </div>
      </div>
    </div>
  </footer>
  #{/block}
  
#{/block}
#{block 'site.js'}
  #{block 'page.js'}
  <!-- Ta aí o que faz o negocio todo funcionar.. -->
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
  #{*<script src="//netdna.bootstrapcdn.com/bootstrap/3.0.0/js/bootstrap.min.js"></script>*}
  #{*<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.0.7/angular.min.js"></script>*}
  <script src="http://code.angularjs.org/1.2.0-rc.2/angular.min.js"></script>
  <script src="http://code.angularjs.org/1.2.0-rc.2/angular-animate.min.js"></script>
  <script src="#{$site.URL}/public/js/helpers/ng-bootstrap.js"></script>
  #{*<script src="#{$site.fullURL}min/?g=core"></script>
  <script src="#{$site.fullURL}min/?g=plugins"></script>*}
  <!-- Iniciando os plugins de redes sociais -->
  <script>
    #{*$(function(){var t=true,_o={dataType:'script',cache:t};
      // google+
      window.___gcfg={lang: 'pt-BR'};
      _o.url='https://apis.google.com/js/plusone.js';$.ajax(_o);
      // twitter
      _o.url='https://platform.twitter.com/widgets.js';$.ajax(_o);
      // facebook
      _o.url='//connect.facebook.net/pt_BR/all.js';_o.success=function(){FB.init({appId:'#{$view.facebook_id}',channelUrl:'#{$site.fullURL}/www/channel.html',cookie:t,status:t,xfbml:t})};$.ajax(_o);
    });*}
  </script>
  #{/block}
#{/block}