<?php

interface SQLArrayAccess extends ArrayAccess, Countable {}

interface ISQLOrdenable {
  function getOrder();
  function setOrder($order);
}

interface ISQLAliasable {
  function getAlias();
  function setAlias($alias);
  function toAlias();
}

interface ISQLNegable {
  function getNegate();
  function setNegate($neg);
}

interface ISQLFunctionable {
  function getFunction();
  function setFunction($function, $params = null);
  function showFunctions($bool);
}

interface ISQLOperationable {
  function getOperator();
  function setOperator($operator);
}

interface ISQLExpressions {
  
}

interface ISQLSelectables {
  
}