// idle.js (c) Alexios Chouchoulas 2009 http://www.bedroomlan.org/coding/detecting-ï¿½idleï¿½-and-ï¿½awayï¿½-timeouts-javascript
// Released under the terms of the GNU Public License version 2.0 (or later).
// Modificado por Raphael Hardt 2013
// Melhorias:
// - suporte pra Prototype retirado
// - retirado o uso de variaveis globais
// - uso do document.hidden para ver se o usuario ainda está naquela aba
window.idleStatus = 'nullgfsgAA';
!function($, win, d) {

  "use strict"; // jshint ;_;
  
  var _idleTimeout = 5000;
  var _awayTimeout = 10000;

  var _idleNow = false;
  var _idleTimestamp = null;
  var _idleTimer = null;
  var _awayNow = false;
  var _awayTimestamp = null;
  var _awayTimer = null;


  function _makeIdle()
  {
    var t = new Date().getTime();
    if (t < _idleTimestamp) {
      //console.log('Not idle yet. Idle in ' + (_idleTimestamp - t + 50));
      _idleTimer = setTimeout(_makeIdle, _idleTimestamp - t + 50);
      return;
    }
    win.idleStatus = 'idle';
    //console.log('** IDLE **');
    _idleNow = true;

    try {
      if (d.onIdle)
        d.onIdle();
    } catch (err) {
    }
  }

  function _makeAway()
  {
    var t = new Date().getTime();
    if (t < _awayTimestamp) {
      //console.log('Not away yet. Away in ' + (_awayTimestamp - t + 50));
      _awayTimer = setTimeout(_makeAway, _awayTimestamp - t + 50);
      return;
    }
    win.idleStatus = 'away';
    //console.log('** AWAY **');
    _awayNow = true;

    try {
      if (d.onAway)
        d.onAway();
    } catch (err) {
    }
  }

  function _active(event)
  {
    var t = new Date().getTime();
    _idleTimestamp = t + _idleTimeout;
    _awayTimestamp = t + _awayTimeout;
    //console.log('not idle.');

    if (_idleNow) {
      setIdleTimeout(_idleTimeout);
    }

    if (_awayNow) {
      setAwayTimeout(_awayTimeout);
    }

    try {
      //console.log('** BACK **');
      win.idleStatus = 'online';
      if ((_idleNow || _awayNow) && d.onBack)
        d.onBack(_idleNow, _awayNow);
    } catch (err) {
    }

    _idleNow = false;
    _awayNow = false;
  }

  function setIdleTimeout(ms)
  {
    _idleTimeout = ms;
    _idleTimestamp = new Date().getTime() + ms;
    if (_idleTimer != null) {
      clearTimeout(_idleTimer);
    }
    _idleTimer = setTimeout(_makeIdle, ms + 50);
    //console.log('idle in ' + ms + ', tid = ' + _idleTimer);
  }

  function setAwayTimeout(ms)
  {
    _awayTimeout = ms;
    _awayTimestamp = new Date().getTime() + ms;
    if (_awayTimer != null) {
      clearTimeout(_awayTimer);
    }
    _awayTimer = setTimeout(_makeAway, ms + 50);
    //console.log('away in ' + ms);
  }

  var hidden, state, visibilityChange;
  if (typeof d.hidden !== "undefined") {
    hidden = "hidden";
    visibilityChange = "visibilitychange";
    state = "visibilityState";
  } else if (typeof d.mozHidden !== "undefined") {
    hidden = "mozHidden";
    visibilityChange = "mozvisibilitychange";
    state = "mozVisibilityState";
  } else if (typeof d.msHidden !== "undefined") {
    hidden = "msHidden";
    visibilityChange = "msvisibilitychange";
    state = "msVisibilityState";
  } else if (typeof d.webkitHidden !== "undefined") {
    hidden = "webkitHidden";
    visibilityChange = "webkitvisibilitychange";
    state = "webkitVisibilityState";
  }
  
  // Detect the API
  try {
    $(function() {
      try {
        $(d)
        .on(visibilityChange, function () {
          if (d[state] === hidden) {
            // away
            //console.log('away from tab change');
            clearTimeout(_idleTimer);
            clearTimeout(_awayTimer);
            _idleTimestamp = _awayTimestamp = (new Date).getTime();
            _makeIdle();
            _makeAway();
          } else {
            // online
            //console.log('active from tab change');
            _active();
          }
        })
        .mousemove(_active)
        .mouseenter(_active)
        .scroll(_active)
        .keydown(_active)
        .click(_active)
        .dblclick(_active)
        ;
        
        setIdleTimeout(_idleTimeout);
        setAwayTimeout(_awayTimeout);
      } catch (err) {}
    });
    
  } catch (err) {
  }

}(window.jQuery, window, document);
