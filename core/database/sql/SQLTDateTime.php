<?php

class SQLTDateTime extends DateTime {
  
  public function __construct($time=null, $object=null) {
    if (!isset($time)) $time = date(self::RFC2822);
    if (is_numeric($time)) {
      $time = date(self::RFC2822, $time);
    }
    $object = new DateTimeZone(date_default_timezone_get());
    parent::__construct($time, $object);
  }
  
  public function __toString() {
    return $this->format(SQLBase::DATEFORMAT_PHP);
  }
  
  public function getLastDayOfMonth() {
    return $this->format('t'); // janeiro: 31, abril: 30
  }
  
  public function getDay() {
    return $this->format('j'); //sem zeros
  }
  
  public function getDayOfYear() {
    return $this->format('z') + 1; // 1 -> 365/366
  }
  
  public function getMonth() {
    return $this->format('n'); //sem zeros
  }
  
  public function getWeekDay() {
    return $this->format('w'); // 0: domingo, 6:sabado
  }
  
  public function getWeekOfYear() {
    return $this->format('W'); // 1 -> 52/53
  }
  
  public function getYear() {
    return $this->format('Y');
  }
  
  public function toTimestamp() {
    if (method_exists($this, 'getTimestamp')) {
      return $this->getTimestamp();
    }
    return (int)$this->format('U');
  }
  
  public function toNiceTime($end_date = null) {
    if (!isset($end_date)) {
      $end_date = time(); // se a data final não for informada, seta ela como "agora"
      $hoje = true;
    } else {
      // transforma a data final em inteiro-unix
      if (!is_numeric($end_date)) {
        if (strpos($end_date, ':') !== false)
          $end_date .= ' ' . date('H:i:s'); // se não houver hora na data, concatena a atual
        $end_date = strtotime($end_date);
      }
    }

    // get timestamp
    $start_date = $this->format('U');

    $nomes = array("segundo", "minuto", "hora", "dia", "semana", "mês", "ano");
    $equiv = array(60, 60, 24, 7, 4.35, 12);

    // verifica o tempo da diferença entre as datas
    $dif = $end_date - $start_date;

    if ($hoje) {
      if ($dif >= 0) {
        $tempo = "%s atrás";
      } else {
        $dif *= -1; // deixa dif positivo
        if ($dif == 1)
          $tempo = "passou-se %s";
        else
          $tempo = "passaram-se %s";
      }
    }
    else {
      $tempo = ($dif < 0 ? '-':'')."%s";
      $dif = abs($dif); // deixa dif positivo
    }

    // calcula o quociente da diferença até chegar no menor quociente possivel
    // para conseguir o maior tempo possivel
    $c = count($equiv) - 1;
    for ($i = 0; $dif >= $equiv[$i] && $i <= $c; $i++) {
      $dif /= $equiv[$i];
    }
    $dif = round($dif);

    if ($dif != 1) {
      if ($nomes[$i] == "mês")
        $nomes[$i] = "meses";
      else
        $nomes[$i] .= "s";
    }

    // retorna uma string, como: '4 horas atrás', 'passou-se 1 dia', '6 minutos'... etc
    return sprintf($tempo, $dif . ' ' . $nomes[$i]);
  }
  
}