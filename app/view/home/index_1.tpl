{block 'page.title' prepend}{$view.title}aaaaaaaaaaaaaaaaaaaaaaa - {/block}
{block 'page.contents'}
  <a href="{$site.URL}/logout">logout</a>
  {literal}
  <div ng-controller="HomeCtrl">
    <p>{{c}} itens encontrados</p>
    <ul>
      <li ng-repeat="item in itens">
        <img ng-src="{{item.img}}" width="50" />
        <input type="checkbox" ng-model="item.checked" />
        <span>{{item.nome}}</span>
      </li>
    </ul>
    <a href="#" ng-click="addItem()">adicionar</a>
    <button onclick="a(teste, teste2)">teste</button>
    <div style="border:1px solid #ccc;">{{idle}}</div>
  </div>
  <div class="well" id="status">ok</div>
  {/literal}
{/block}
{block 'ad.728'}{/block}
{block 'page.js' append}
  <script src="{$site.URL}/public/js/helpers/idle.js"></script>
  <script>
    /*document.onBack = function () {
      $('#status').text('ok ');
      var scope = angular.element($('html')[0]).scope();
      scope.status = 'fsdfsd';
    }
    document.onIdle = function () {
      $('#status').text('idle ');
      teste = 'idle';
    }
    document.onAway = function () {
      $('#status').text('away ');
      teste = 'away';
    }*/
    
    function a() {
      console.log(arguments);
    }
    
    function HomeCtrl($scope, $timeout, $http, $window) {
      
      $http({ method: 'GET', url: 'teste.json'}).
        success(function(data, status, headers, config) {
        // this callback will be called asynchronously
        // when the response is available
          $scope.itens = data;
          $scope.c = data.length;
        }).
        error(function(data, status, headers, config) {
        // called asynchronously if an error occurs
        // or server returns response with an error status.
      });
      
      $scope.addItem = function () {
          /*$timeout(function () {
            //$scope.itens = [];
            $scope.itens.push({ img: '123.jpg', nome: 'Teste'+($scope.c++), checked:$scope.c % 2 == 0 });
          }, 1000);*/
          $http({ method: 'GET', url: 'teste.json'}).
            success(function(data, status, headers, config) {
            // this callback will be called asynchronously
            // when the response is available
              angular.forEach(data, function (val, key) {
                this.push(val);
              }, $scope.itens);
              $scope.c += data.length;
            }).
            error(function(data, status, headers, config) {
            // called asynchronously if an error occurs
            // or server returns response with an error status.
          });
      };
      
      $window.idleStatus = $scope.idle;
    }
  </script>
{/block}