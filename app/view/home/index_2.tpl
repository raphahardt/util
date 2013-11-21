{block 'page.title' prepend}{$view.title}{/block}
{block 'page.contents'}
  <a href="{$site.URL}/logout">logout</a>
  {literal}
  <div ng-controller="HomeCtrl">
    <p>{{c}} itens encontrados</p>
    <ul>
      <li ng-repeat="(key,item) in itens | orderBy:'ord'">
        <img ng-src="{{item.img}}" />
        <input type="checkbox" ng-model="item.checked" />
        <span>{{item.nome}} (ordem {{item.ord}})</span>
        <a href="#" ng-click="sobe(key, key-1)" ng-show="item.ord > 1">sobe</a>
        |
        <a href="#" ng-click="desce(key, key+1)" ng-show="item.ord < itens.length">desce</a>
      </li>
    </ul>
    <ul>
      <li ng-repeat="file in myfile">
        {{file.name}}
      </li>
    </ul>
    <a href="#" ng-click="addItem()">adicionar</a>
    <file name="images" id="file" ng-model="myfile" multiple/>
    {{myfile.files}}
    <div style="border:1px solid #ccc;">{{idle}}</div>
    <input type="text" ng-model="cok" size="50" />
  </div>
  <div class="well" id="status">ok</div>
  {/literal}
{/block}
{block 'page.js' append}
  <script>
    var AA;
    function a() {
      AA.itens.push({ img: '123.jpg', nome: 'AAAAA', checked:true, ord: 1 });
      AA.$apply();
    }
    function HomeCtrl($scope, $timeout, $http, $window) {
      
      //alert($.cookie('PHPSESSID'));
      
      //$scope.cok = $.cookie('PHPSESSID');
      
      /*$http({ method: 'GET', url: 'teste2.json', cache:true}).
        success(function(data, status, headers, config) {
        // this callback will be called asynchronously
        // when the response is available
          $scope.itens = data;
          $scope.c = data.length;
        }).
        error(function(data, status, headers, config) {
          //alert('erro!');
          // called asynchronously if an error occurs
          // or server returns response with an error status.
        });*/
    
      $scope.itens = [];
      $scope.c = 0;
      
      AA = $scope;
      
      $scope.handleFiles = function () {
        var files = $scope.myfile;
        for (var i = 0; i < files.length; i++) {
          var file = files[i];
          var imageType = /image.*/;

          if (!file.type.match(imageType)) {
            continue;
          }

          var reader = new FileReader();
          reader.onload = (function(scope, file) { return function(e) { 
          
            scope.itens.push({ img: e.target.result, nome: file.name, ord:1 });
            scope.$apply();
          
          }; })($scope, file);
          reader.readAsDataURL(file);
        }
      }
      
      $scope.addItem = function () {
          $timeout(function () {
            //$scope.itens = [];
            $scope.itens.push({ img: '{$site.URL}/static/'+(Math.random() > 0.5?'123':'456')+'-60.jpg', nome: (Math.random()*30)+'Teste'+($scope.c++), checked:$scope.c % 2 == 0, ord: $scope.c });
          }, 100);
          /*$http({ method: 'GET', url: 'teste2.json', cache:true}).
            success(function(data, status, headers, config) {
            // this callback will be called asynchronously
            // when the response is available
              angular.forEach(data, function (val, key) {
                this.push(val);
              }, $scope.itens);
              $scope.c += data.length;
            }).
            error(function(data, status, headers, config) {
              //alert('erro!');
              // called asynchronously if an error occurs
              // or server returns response with an error status.
            });*/
      };
      
      $scope.sobe = function (i, j) {
        $scope.itens[i].ord--;
        $scope.itens[j].ord++;
        var aux;
        aux = $scope.itens[i];
        $scope.itens[i]=$scope.itens[j];
        $scope.itens[j]=aux;
      }
      
      $scope.desce = function (i, j) {
        $scope.itens[i].ord++;
        $scope.itens[j].ord--;
        var aux;
        aux = $scope.itens[i];
        $scope.itens[i]=$scope.itens[j];
        $scope.itens[j]=aux;
      }
      
    }
  </script>
{/block}