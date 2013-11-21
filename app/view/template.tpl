#{** ------------------------------------------------------------------
 * template.tpl
 * --------------------------------------------------------------------
 * É o layout do seu site. Deve-se usar os blocos 'site.[nomebloco]' para alterar/substituir 
 * a interface do site. As paginas de conteudo usam 'view.[nomebloco]' para alterar conteudos
 * sem mudar o layout
 *}


#{block 'site.head' append}
  
  <!-- Fonts (ficam antes pois devem carregar primeiro, evita flick) -->
  <link href='http://fonts.googleapis.com/css?family=Comfortaa:400,700' rel='stylesheet' type='text/css'>
  
  <!-- CSS gerais -->
  <link rel="stylesheet" type="text/css" href="#{$site.fullURL}/public/css/app.css">
  
  #{block 'view.head'}#{/block}
  #{* ... *}
#{/block}

#{** -----------------------------------------------------------------
 * Javascripts que ficam na <head> do site.
 * Use 'view.head.javascript' com 'append' para acrescentar outros scripts na página em questão
 * @block view.head.javascript
 *}
#{block 'site.head.javascript' append}
  
  #{*aqui colocar scripts que vão ser comuns para todas as paginas e que devem ficar no head
  , como o modernizr, entre outros que devem ficar no head*}
  
  #{block 'view.head.javascript'}#{/block}
  #{* ... *}
#{/block}

#{block 'site.body.header' append}
  <div id="fb-root"></div>
  
  #{block 'view.body.header'}
    
    <!-- Header do site -->
    <header class="re-header">
      <div class="container">
        <div class="re-header-row">
          <div class="re-header-brand">
            <hgroup class="logo">
              <h1><a href="#{'home'|link}">#{$site.title}</a></h1>
              <h2>#{$site.subtitle}</h2>
            </hgroup>
            <nav class="re-menu">
              <ul>
                <li><a href="#{'conheca'|link}">Conheça</a></li>
                <li><a href="#{'descubra'|link}">Descubra</a></li>
                <li><a href="#{'publique'|link}">Publique</a></li>
              </ul>
            </nav>
          </div>
          <div class="re-header-gsearch" ng-controller="TesteController">
            <form action="#{'series'|link}">
              <div class="input-group">
                <input type="text" placeholder="O que vou ler?" name="g" ng-model="globalSearch" 
                       options="options" on-select="onSelect" class="form-control" global-search/>
                <div class="input-group-btn">
                  <button type="submit" class="btn btn-primary">OK!</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </header>
    <!-- fim Header -->
  
  #{/block}
  #{* ... *}
#{/block}

#{block 'site.body.content' append}
  #{* ... *}
  #{block 'view.body.content'}#{/block}
  #{* ... *}
#{/block}

#{** -----------------------------------------------------------------
 * Javascripts que ficam no <body> do site.
 * Use 'view.body.javascript' com 'append' para acrescentar outros scripts na página em questão
 * @block view.body.javascript
 *}
#{block 'site.body.javascript' append}
  <!-- Iniciando os plugins de redes sociais -->
  <script>
    // google+
    window.___gcfg={lang: '#{$site.lang}'};
    $script('https://apis.google.com/js/plusone.js', 'gplus');
    
    // twitter
    $script('https://platform.twitter.com/widgets.js', 'twitter');
    
    #{if $view.facebook_id}
    // facebook
    $script('http://connect.facebook.net/#{$site.lang_alt}/all.js', 'facebook', function () {
      var t=true;FB.init({appId:'#{$view.facebook_id}',channelUrl:'#{$site.fullURL}/channel.html',cookie:t,status:t,xfbml:t})
    });
    #{/if}
  </script>
  
  #{block 'view.body.javascript'}#{/block}
  
  <script>
    // dependencies
    $script.ready('angularjs', function () {
      $script(['helper/typeahead', 'directive/global-search'], 'app');
    });
    
    // app
    $script.ready('app', function () {
      
      angular.module('teste', ['global-search'])
      .controller('TesteController', function ($scope) {
        $scope.onSelect = function (val) {
          console.log(val);
        }
        $scope.options = [{
          name: 'generos',
          header: '<div class="header">Gêneros</div>',
          limit: 6,
          local: [
            'Ação',
            'Aventura',
            'Comédia',
            'Luta',
            'Shonen',
            'Terror',
            'Drama',
            'Romance',
            'Slice of life',
            'Shoujo',
            'Tirinhas'
          ],
          template: '<i class="fa fa-star"></i> {{ value }}'
        },
        {
          name: 'series',
          header: '<div class="header">Séries</div>',
          valueKey: 'titulo',
          limit: 3,
          prefetch: '#{$site.URL}/teste.json',
          //remote: '#{$site.URL}/teste?s=%QUERY', 
          //template: '<img ng-src="{{ img }}" height=100 /><strong>{{ titulo }}</strong> <span>{{ genero }}</span>'
          template: '<div class="re-gs-serie">' +
                      '<div class="re-gs-serie-img"><img ng-src="{{ img }}" /></div>' +
                      '<div class="re-gs-serie-content">' + 
                        '<a ng-href="serie/{{ url }}" class="btn btn-sm btn-primary">Leia agora</a>' +
                        '<h2 class="title">{{ titulo }}</h2>' +
                        '<small class="author">por {{ autor }}</small>' +
                        '<p class="sinopse">{{ sinopse }}</p>' +
                      '</div>' +
                    '</div>'
        }];
      })
      
      angular.bootstrap($('.re-header-gsearch')[0], ['teste'])
    });
  </script>
  
  #{* ... *}
#{/block}