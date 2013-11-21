#{block 'page.title' prepend}Teste - #{/block}
#{block 'site.body.content'}
  jfisdjfsdoi
#{/block}
#{block 'view.body.javascript2' append}
  <script src="#{'minify'|link:'js/helpers/typeahead.js'}"></script>
  <script>
    angular.module('teste', [])
    .factory('AutoCompleteEngine', function ($rootScope, $compile) {
      // objeto simples que faz um wrap do service $compiler
      // é necessario pois o typeahead requer um objeto que contenha a função compile(template)
      // que retorna um objeto que tenha uma função render(contexto), e o angular não funciona
      // dessa forma
      // (ver https://github.com/twitter/typeahead.js#template-engine-compatibility )
      var Engine = function(){};
      Engine.prototype.constructor = Engine;
      Engine.prototype.compile = function (template) {
        return { 
          render: function (obj) {
            // cria um novo scope para o plain object que vai ser passado
            var s = $rootScope.$new();
            // mescla o objeto com o scope recem criado
            angular.extend(s, obj);

            // compila e retorna o html final
            var r = $compile('<div>'+template+'</div>')(s); // envolve com div pois quero apenas o conteudo (html())
            s.$digest(); // força o template a "interpolar"
            return r.html();
          }
        };
      }
      return new Engine;
    })
    .directive('myAutoComplete', function (AutoCompleteEngine) {
      return {
        require: '?ngModel',
        restrict: 'AC',
        link: function postLink (scope, elem, attr, ngModel) {
          if(!ngModel) return; // do nothing if no ng-model
          
          var o = {}, 
              onselect = scope.$eval(attr.onSelect) || function(){}, 
              options = scope.$eval(attr.options) || {}, 
              defaults = {
                engine: AutoCompleteEngine
              };
          
          // acerta as opções
          if (angular.isArray(options)) {
            // array
            o = [];
            for (var i=0;i<options.length;i++) {
              var _o = {};
              angular.extend(_o, defaults, options[i]);
              o.push(_o);
            }
          } else {
            // objects
            angular.extend(o, defaults, options);
          }
          
          // como o model será atualizado
          var validObjectSelected = null;
          function update() {
            if (validObjectSelected && validObjectSelected.text === elem.val()) {
              ngModel.$setViewValue(validObjectSelected.value);
            } else {
              validObjectSelected = null;
              ngModel.$setViewValue(elem.val());
            }
          }
          
          // cria o typeahead
          elem.typeahead(o)
          .on('blur keyup change', function(event) {
            scope.$apply(update);
          })
          .on('typeahead:selected', function (event, val) {
            validObjectSelected = {
              text: elem.val(),
              value: val
            };
            scope.$apply(update);
            
            if (angular.isFunction(onselect)) {
              var r = onselect.call(elem, val);
              // função dever ser:
              // function (valor) { this; // <input> }
            }
          });
          // inicia o objeto
          update();
        }
      };
    }).controller('TesteController', function ($scope) {
      $scope.onSelect = function (val) {
        console.log(val);
      }
      $scope.options = [{
        name: 'pessoas',
        header: '<div>Pessoas</div>',
        valueKey: 'descricao',
        remote: '#{$site.URL}/teste?s=%QUERY',
        local: [
          {
            descricao: 'Joao paulo',
            get: 1234
          },
          {
            descricao: 'Maria Joaquina',
            get: 54543
          },
          {
            descricao: 'José da silva',
            get: 908
          },
          {
            descricao: 'Helio de la peña',
            get: 652
          },
          {
            descricao: 'Oswald Andrade', 
            get: 12
          }
          
        ],
        template: '<strong>{{ descricao }}</strong> <span>{{ get }}</span>'
      },
      {
        name: 'pessoas2',
        header: '<div>Outras pessoas</div>',
        valueKey: 'teste',
        local: [
          {
            teste: 'abc',
            get: 1234
          },
          {
            teste: 'jbc',
            get: 54543
          },
          {
            teste: 'alele',
            aaa: true,
            get: 908
          },
          {
            teste: 'nanana',
            get: 652
          },
          {
            nome: 'aaaa', 
            get: 12
          }
          
        ],
        template: '<div ng-class="{ red: aaa }"><strong>{{ teste }}</strong> <span>{{ get }}</span></div>'
      }];
    })
  </script>
#{/block}
#{block 'page.contents3'}
  <ul>
    #{foreach [1,2,3,5] as $id}
      <li><a href="#{'teste'|link:testeteste:$id}">link #{$id}</a></li>
      <li><a href="#{'teste'|link:[a=>testeteste, id=>$id]}">link #{$id} (2 opcao)</a></li>
    #{/foreach}
  </ul>
#{/block}
#{block 'page.contents2'}
  <style>
    .ng-cloak {
      display:none !important;
    }
    .animate-if.ng-enter, .animate-if.ng-leave {
      -webkit-transition:all ease 0.3s;
      -moz-transition:all ease 0.3s;
      -o-transition:all ease 0.3s;
      transition:all ease 0.3s;
    }

    .animate-if.ng-enter,
    .animate-if.ng-leave.ng-leave-active {
      opacity:0;
      /*margin-top: -100px;*/
      -webkit-transform: scale(0);
    }

    .animate-if.ng-enter.ng-enter-active,
    .animate-if.ng-leave {
      opacity:1;
      margin-top:0;
      -webkit-transform: scale(1);
    }
    .translucid {
      opacity:0.5;
    }
    
    .list-placeholder {
      background: rgba(0,0,0, .3);
      border: 1px dotted rgba(0,0,0, .5);
      display: block;
      min-height: 100px;
    }
    
  </style>

  <!-- The file upload form used as target for the file upload widget -->
  <form action="#{$site.URL}/upload/" method="POST" enctype="multipart/form-data">
    <input type="file" name="f"/>
    <input type="submit"/>
  </form>
  <form id="fileupload" action="#{$site.URL}/upload/" method="POST" enctype="multipart/form-data" ng-app="demo" id="ng-app" ng-controller="DemoFileUploadController" data-file-upload="options" ng-class="{ 'fileupload-processing': processing() || loadingFiles }">
    <!-- Redirect browsers with JavaScript disabled to the origin page -->
    <noscript><input type="hidden" name="redirect" value="#{$site.URL}/"></noscript>
    <div class="col-xs-3">
      <div class="input-group">
        <input type="text" name="folder" class="form-control" value="#{$folder}" onchange="window.location.href='#{$site.URL}/?folder='+this.value"/>
        <span class="input-group-btn">
          <button class="btn btn-primary" type="button">Go!</button>
        </span>
      </div>
    </div>
    <!-- The fileupload-buttonbar contains buttons to add/delete files and start/cancel the upload -->
    <div class="row fileupload-buttonbar">
      <div class="col-lg-7">
        <!-- The fileinput-button span is used to style the file input field as button -->
        <span class="btn btn-default btn-file" ng-class="{ disabled: disabled }">
          <span><i class="fa fa-file"></i> Escolher arquivos</span>
          <input type="file" name="files[]" multiple ng-disabled="disabled">
        </span>
        <button type="button" class="btn btn-default start" ng-click="submit()">
          <span>Start upload</span>
        </button>
        <button type="button" class="btn btn-default cancel" ng-click="cancel()" popover-placement="top" popover="On the Top!">
          <span>Cancel upload</span>
        </button>
        
        <div class="btn-group">
          <button type="button" class="btn btn-default" ng-model="luz" btn-checkbox> <i class="fa fa-lightbulb-o"></i> <span ng-switch on="!!luz"><span ng-switch-when="true">Apagar</span><span ng-switch-default>Acender</span></span> a luz</button>
        </div>
        <!-- The loading indicator is shown during file processing -->
        <div class="fileupload-loading"></div>
      </div>
      <!-- The global progress information -->
      <div class="col-lg-5 fade" ng-class="{ in: active()}">
        <!-- The global progress bar -->
        <div class="progress progress-striped active" data-file-upload-progress="progress()"><div class="progress-bar progress-bar-success" ng-style="{ width: num + '%'}"></div></div>
        <!-- The extended global progress information -->
        <div class="progress-extended">&nbsp;</div>
      </div>
    </div>
    
    <!-- The table listing the files available for upload/download -->
    <ul class="files ng-cloak" ui-sortable="sortableOpts" ng-model="queue">
      <li ng-repeat="file in queue | orderBy:'ord'" class="col-lg-3 col-md-4 col-sm-6" ng-class-odd="'odd'">
        
        <div class="file-preview" ng-switch data-on="!!file.thumbnailUrl">
          {{ file.ord }}
          <div style="background:#ccc">Data: {{ file.data }}</div>
          <div class="preview" ng-switch-when="true">
            <a ng-href="{{ file.url }}" title="{{ file.name }}" download="{{ file.name }}" data-gallery><img ng-src="{{ file.thumbnailUrl }}" alt=""></a>
          </div>
          <div class="preview translucid" ng-switch-default data-file-upload-preview="file"></div>
        </div>
        
        <div class="file-name handler">
          <p class="name" ng-switch data-on="!!file.url">
            <span ng-switch-when="true" ng-switch data-on="!!file.thumbnailUrl">
              <a ng-switch-when="true" ng-href="{{ file.url }}" title="{{ file.name }}" download="{{ file.name }}" data-gallery>{{ file.name }}</a>
              <a ng-switch-default ng-href="{{ file.url }}" title="{{ file.name }}" download="{{ file.name }}">{{ file.name }}</a>
            </span>
            <span ng-switch-default>{{ file.name }}</span>
          </p>
          <div ng-show="file.error"><span class="label label-danger">Error</span> {{ file.error }}</div>
        </div>
        
        <div class="file-size">
          <p class="size">{{ file.size | formatFileSize }}</p>
          <div class="progress progress-striped active fade" ng-class="{ pending: 'in' }[file.$state()]" data-file-upload-progress="file.$progress()"><div class="progress-bar progress-bar-success" ng-style="{ width: num + '%'}"></div></div>
        </div>
        
        <div class="file-actions" ng-hide="file.$state() == 'pending'">
          <button type="button" class="btn btn-primary start" ng-click="file.$submit()" ng-hide="!file.$submit">
            <span>Start</span>
          </button>
          <button type="button" class="btn btn-warning cancel" ng-click="file.$cancel()" ng-hide="!file.$cancel">
            <span>Cancel</span>
          </button>
          <button ng-controller="FileDestroyController" type="button" class="btn btn-danger destroy" ng-click="file.$destroy()" ng-hide="!file.$destroy">
            <span>Delete</span>
          </button>
        </div>
        
      </li>
    </ul>
  </form>
  <div style="clear:left;">&nbsp;</div>
  <div class="row">
    <div class="col-xs-12">
      <h1>Ut enim ad minim veniam</h1>
      <p>
        Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.
      </p>
      <p>
        Curabitur pretium tincidunt lacus. Nulla gravida orci a odio. Nullam varius, turpis et commodo pharetra, est eros bibendum elit, nec luctus magna felis sollicitudin mauris. Integer in mauris eu nibh euismod gravida. Duis ac tellus et risus vulputate vehicula. Donec lobortis risus a elit. Etiam tempor. Ut ullamcorper, ligula eu tempor congue, eros est euismod turpis, id tincidunt sapien risus a quam. Maecenas fermentum consequat mi. Donec fermentum. Pellentesque malesuada nulla a mi. Duis sapien sem, aliquet nec, commodo eget, consequat quis, neque. Aliquam faucibus, elit ut dictum aliquet, felis nisl adipiscing sapien, sed malesuada diam lacus eget erat. Cras mollis scelerisque nunc. Nullam arcu. Aliquam consequat. Curabitur augue lorem, dapibus quis, laoreet et, pretium ac, nisi. Aenean magna nisl, mollis quis, molestie eu, feugiat in, orci. In hac habitasse platea dictumst.
      </p>
      <h2> Curabitur non elit ut libero</h2>
      <p>
        Fusce convallis, mauris imperdiet gravida bibendum, nisl turpis suscipit mauris, sed placerat ipsum urna sed risus. In convallis tellus a mauris. Curabitur non elit ut libero tristique sodales. Mauris a lacus. Donec mattis semper leo. In hac habitasse platea dictumst. Vivamus facilisis diam at odio. Mauris dictum, nisi eget consequat elementum, lacus ligula molestie metus, non feugiat orci magna ac sem. Donec turpis. Donec vitae metus. Morbi tristique neque eu mauris. Quisque gravida ipsum non sapien. Proin turpis lacus, scelerisque vitae, elementum at, lobortis ac, quam. Aliquam dictum eleifend risus. In hac habitasse platea dictumst. Etiam sit amet diam. Suspendisse odio. Suspendisse nunc. In semper bibendum libero.
      </p>
      <p>
        Proin nonummy, lacus eget pulvinar lacinia, pede felis dignissim leo, vitae tristique magna lacus sit amet eros. Nullam ornare. Praesent odio ligula, dapibus sed, tincidunt eget, dictum ac, nibh. Nam quis lacus. Nunc eleifend molestie velit. Morbi lobortis quam eu velit. Donec euismod vestibulum massa. Donec non lectus. Aliquam commodo lacus sit amet nulla. Cras dignissim elit et augue. Nullam non diam. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. In hac habitasse platea dictumst. Aenean vestibulum. Sed lobortis elit quis lectus. Nunc sed lacus at augue bibendum dapibus.
      </p>
      <p>
        Aliquam vehicula sem ut pede. Cras purus lectus, egestas eu, vehicula at, imperdiet sed, nibh. Morbi consectetuer luctus felis. Donec vitae nisi. Aliquam tincidunt feugiat elit. Duis sed elit ut turpis ullamcorper feugiat. Praesent pretium, mauris sed fermentum hendrerit, nulla lorem iaculis magna, pulvinar scelerisque urna tellus a justo. Suspendisse pulvinar massa in metus. Duis quis quam. Proin justo. Curabitur ac sapien. Nam erat. Praesent ut quam.
      </p>
    </div>
  </div>
  
#{/block}
#{block 'ad.728'}#{/block}
#{block 'page.js2' append}
  <!-- The jQuery UI widget factory, can be omitted if jQuery UI is already included -->
  <script src="#{$site.URL}/public/js/helpers/jupload/vendor/jquery.ui.widget.js"></script>
  <!-- The Load Image plugin is included for the preview images and image resizing functionality -->
  <script src="#{$site.URL}/public/js/helpers/jupload/plugins/load-image.min.js"></script>
  <!-- The Canvas to Blob plugin is included for image resizing functionality -->
  <script src="#{$site.URL}/public/js/helpers/jupload/plugins/canvas-to-blob.min.js"></script>
  
  <script src="#{$site.URL}/public/js/helpers/jupload/jquery.iframe-transport.js"></script>
  <!-- The basic File Upload plugin -->
  <script src="#{$site.URL}/public/js/helpers/jupload/jquery.fileupload.js"></script>
  <!-- The File Upload processing plugin -->
  <script src="#{$site.URL}/public/js/helpers/jupload/jquery.fileupload-process.js"></script>
  <!-- The File Upload image preview & resize plugin -->
  <script src="#{$site.URL}/public/js/helpers/jupload/jquery.fileupload-image.js"></script>
  <!-- The File Upload validation plugin -->
  <script src="#{$site.URL}/public/js/helpers/jupload/jquery.fileupload-validate.js"></script>
  <!-- The File Upload Angular JS module -->
  <script src="#{$site.URL}/public/js/helpers/jupload/jquery.fileupload-angular.js"></script>
  <!-- The main application script -->
  <script src="#{$site.URL}/public/js/core/jquery-ui-1.10.3.custom.min.js"></script>
  <script src="#{$site.URL}/public/js/helpers/ng-sortable.js"></script>
  
  <script>
  
(function () {
    'use strict';

    var url = '#{$site.URL}/upload/?folder=#{$folder}';

#{literal}
    angular.module('demo', [
        'blueimp.fileupload',
        'ngAnimate',
        'ui.sortable',
        'ui.bootstrap'
    ])
        .config([
            '$httpProvider', '$interpolateProvider', 'fileUploadProvider',
            function ($httpProvider, $interpolateProvider, fileUploadProvider) {
                //$interpolateProvider.startSymbol('{{').endSymbol('}}');
                
                delete $httpProvider.defaults.headers.common['X-Requested-With'];
                fileUploadProvider.defaults.redirect = window.location.href.replace(
                    /\/[^\/]*$/,
                    '/cors/result.html?%s'
                    );
                  // settings
                  angular.extend(fileUploadProvider.defaults, {
                      previewMaxWidth: 200,
                      previewMaxHeight: 320,
                      limitConcurrentUploads: 3,
                      // Enable image resizing, except for Android and Opera,
                      // which actually support image resizing, but fail to
                      // send Blob objects via XHR requests:
                      disableImageResize: /Android(?!.*Chrome)|Opera/
                              .test(window.navigator.userAgent),
                      maxFileSize: 5000000,
                      acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i
                    });
                }
                ])
                /*.directive('sortable', function () {
                  return {
                    restrict: 'AC',
                    require: 'ngModel',
                    scope: true,
                    link: function postLink(scope, element, attr, ngModel) {
                      var dragStart = function (e, ui) {
                        ui.item.data('start', ui.item.index());
                      };
                      var dragEnd = function(e, ui) {
                        var start = ui.item.data('start'),
                            end = ui.item.index();

                        //ngModel.$viewValue.splice(end, 0, ngModel.$viewValue.splice(start, 1)[0]);
                        
                        //if (ui.item.sortable.resort || ui.item.sortable.relocate) {
                          scope.$apply();
                        //}
                      };
                      var sortableEle = $(element).sortable({
                          start: dragStart,
                          update: dragEnd
                      });
                      
                      ngModel.$render = function() {
                        element.sortable( "refresh" );
                      };
                      
                      // olha qualquer mudança no array
                      scope.$watchCollection(attr.ngModel, function () {
                        // atualiza o jquery ui sortable pra reconhecer todos elementos
                        //if (sortableEle)
                        element.sortable( "refresh" );
                          //sortableEle.refresh();
                      });
                    }
                  };
                })*/

                .controller('DemoFileUploadController', [
                  '$scope', '$http', '$timeout', '$window',
                  function($scope, $http, $timeout) {
                    $scope.options = {
                      url: url
                      /*submit: function (e, data) {
                        //console.log(e);
                        console.log(data);
                        var inputs = $(data.form.context).find(':input');
                        if (inputs.filter('[required][value=""]').first().focus().length) {
                            return false;
                        }
                        data.formData = inputs.serializeArray();
                      }*/
                    };
                    $scope.sortableOpts = {
                      //placeholder: 'list-placeholder col-lg-3 col-md-4 col-sm-6',
                      handle: '.handler',
                      stop: function(e, ui) { 
                        //$.each(ui.item)
                        //console.log(ui.item);
                        //console.log(ui.item.index());
                        /*angular.forEach($scope.queue, function (val, i) {
                          val.ord = i;
                        });*/
                        //ui.
                        force = true;
                      }
                      //axis: 'x'
                    };
                    
                    var force = false;
                    $scope.$watchCollection('queue', function (newval, oldval) {
                      var count = 0;
                      var requests = { ordem_file:[], ordem:[] };
                      angular.forEach(newval, function (val) {
                        if (force || !angular.isDefined(val.ord)) {
                          if (val.url) {
                            requests.ordem_file.push(val.name);
                            requests.ordem.push(count);
                          }
                          val.ord = count;
                        }
                        count++;
                      });
                      if (requests.ordem_file.length) {
                        $http.post(url, { ordem_file: requests.ordem_file, ordem: requests.ordem });
                      }
                      force = false;
                      //console.log('mudou a ordem! (tamanho: '+count+')');
                    });
    
                    /*$scope.dragStart = function(e, ui) {
                      ui.item.data('start', ui.item.index());
                    }
                    $scope.dragEnd = function(e, ui) {
                      var start = ui.item.data('start'),
                          end = ui.item.index();

                      $scope.queue.splice(end, 0, $scope.queue.splice(start, 1)[0]);

                      $scope.$apply();
                    };

                    var sortableEle = $('ul.files').sortable({
                        start: $scope.dragStart,
                        update: $scope.dragEnd
                    });*/
    
                    $scope.loadingFiles = true;
                    $http.get(url)
                      .then(
                      function(response) {
                        $scope.loadingFiles = false;
                        $scope.queue = response.data.files || [];
                      },
                      function() {
                        $scope.loadingFiles = false;
                      }
                    );
                  }
                ])

                .controller('FileDestroyController', [
                  '$scope', '$http',
                  function($scope, $http) {
                    var file = $scope.file,
                            state;
                    if (file.url) {
                      file.$state = function() {
                        return state;
                      };
                      file.$destroy = function() {
                        state = 'pending';
                        return $http({
                          url: file.deleteUrl,
                          method: file.deleteType
                        }).then(
                          function(response) {
                            if (response.data[file.name]) {
                              state = 'resolved';
                              $scope.clear(file);
                            } else {
                              state = 'rejected';
                            }
                          },
                          function() {
                            state = 'rejected';
                          }
                        );
                      };
                    } else if (!file.$cancel && !file._index) {
                      file.$cancel = function() {
                        $scope.clear(file);
                      };
                    }
                  }
                ]);

              }());

              //IE sucks :(
              /*$(function () {
                angular.bootstrap($('#ng-app'));
                alert('iniciou!');
              });*/

  #{/literal}
  </script>
#{/block}