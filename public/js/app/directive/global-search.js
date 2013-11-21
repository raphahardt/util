/* 
 * Auto Complete para o search global do site
 * Usa typeahead http://twitter.github.io/typeahead.js/
 */
$script.ready('angularjs', function() {

  angular.module('global-search', [])
          .factory('GlobalSearchTplEngine', function($rootScope, $compile) {
            // objeto simples que faz um wrap do service $compiler
            // é necessario pois o typeahead requer um objeto que contenha a função compile(template)
            // que retorna um objeto que tenha uma função render(contexto), e o angular não funciona
            // dessa forma
            // (ver https://github.com/twitter/typeahead.js#template-engine-compatibility )
            var Engine = function() {
            };
            Engine.prototype.constructor = Engine;
            Engine.prototype.compile = function(template) {
              return {
                render: function(obj) {
                  // cria um novo scope para o plain object que vai ser passado
                  var s = $rootScope.$new(true); // true pq o scope é isolado
                  // mescla o objeto com o scope recem criado
                  angular.extend(s, obj);

                  // compila e retorna o html final
                  var r = $compile('<div>' + template + '</div>')(s); // envolve com div pois quero apenas o conteudo (html())
                  s.$digest(); // força o template a "interpolar"
                  return r.html();
                }
              };
            }
            return new Engine;
          })
          .directive('globalSearch', function(GlobalSearchTplEngine) {
            return {
              require: '?ngModel',
              restrict: 'AC',
              link: function postLink(scope, elem, attr, ngModel) {
                if (!ngModel)
                  return; // do nothing if no ng-model

                var o = {},
                        onselect = scope.$eval(attr.onSelect) || function() {},
                        options = scope.$eval(attr.options) || {},
                        defaults = {
                          engine: GlobalSearchTplEngine
                        };

                // acerta as opções
                if (angular.isArray(options)) {
                  // array
                  o = [];
                  for (var i = 0; i < options.length; i++) {
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
                  .on('typeahead:selected', function(event, val) {
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
          })
})