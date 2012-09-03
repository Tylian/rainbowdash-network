<?php

if (!defined('STATUSNET')) {
    // This check helps protect against security problems;
    // your code file can't be executed directly from the web.
    exit(1);
}

class BrowserPoniesPlugin extends Plugin
{
    function onEndShowStyles($action) {
        $action->cssLink($this->path('gui.css'));
    }
    
    function onEndShowScripts($action)
    {
        $path = $this->path('');
        $config = <<<HERE
var BrowserPoniesInit = false;
BrowserPoniesPath = '$path';

function configCallback() {
    if(!BrowserPonies.running()) { 
        if(!BrowserPoniesInit) {
            BrowserPonies.loadConfig(BrowserPoniesConfig);
            BrowserPoniesInit = true;
        }
        BrowserPonies.start();
    }
    else {
        BrowserPonies.stop();
    }
}

$(function() {
    $('#browserponies-start').click(function(event){
        event.preventDefault();
        if(!BrowserPoniesInit) {
            $.ajax({
                url: BrowserPoniesPath + "browserponies.min.js",
                success: function(data) {
                    eval(data);
                    $.ajax({
                        url: BrowserPoniesPath + "ponycfg.js",
                        success: function(data) {
                            eval(data);
                            configCallback();
                        }
                    });
                },
            });
        }
        else configCallback();
    });
    $('#browserponies-plus').click(function(event) {
        event.preventDefault();
        BrowserPonies.spawnRandom();
    });
    $('#browserponies-minus').click(function(event) {
        event.preventDefault();
        BrowserPonies.unspawnAll();
    });
    $('#browserponies-name').keydown(function(event) {
        if(event.keyCode == 13) {
            var ponies = $(this).val().split(',');
            for(each in ponies) {
                BrowserPonies.spawn(ponies[each], 1);
            }
            $(this).val('');
        }
    }).keyup(function() {
        var lastValue = $(this).val().substr($(this).val().lastIndexOf(',') + 1).toLowerCase();
        var ponies = BrowserPonies.ponies();
        
        $('#browserponies-suggest').html('');
        
        if(lastValue.length == 0) {
            $('#browserponies-suggest').hide();
            return;
        }
        
        $('#browserponies-suggest').show();
        
        var limit = 4, text = "";
        for(each in ponies) {
            if(each.indexOf(lastValue) >= 0 || ponies[each].name.indexOf(lastValue) > 0) {
                text = ponies[each].name.replace(new RegExp("(" + (lastValue+'').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, "\\$1") + ")", 'gi'), "<b>$1</b>");
                $('#browserponies-suggest').append('<div class="browserponies-suggestion"><img src="' + ponies[each].all_behaviors[0].rightimage + '" /><br />' + text + '</div>');
                if(--limit == 0) {
                    break;
                }
            }
        }
        
        if(limit == 4) {
            $('#browserponies-suggest').hide();
            return;
        }
        
        $('#browserponies-suggest img').load(function() {
            var offset = $('#browserponies-name').offset();
            offset.top -= $('#browserponies-suggest').height() + 6;
            $('#browserponies-suggest').offset(offset);
        });
        
        var offset = $('#browserponies-name').offset();
        offset.top -= $('#browserponies-suggest').height() + 6;
        $('#browserponies-suggest').offset(offset);

        $('.browserponies-suggestion').click(function() {
            BrowserPonies.spawn($(this).text());
            $('#browserponies-name').val('');
            $('#browserponies-suggest').hide();
        });
    });
});

HERE;
        $config = str_replace(array("\n", '  '), '', $config);
        $action->inlineScript($config);

        return true;
    }

    function onStartShowExportData($action) {
        $buttons = <<<HERE
<div class="browserponies">
    Browser Ponies<br />
    <input type="text" id="browserponies-name" />
    <a id="browserponies-start" class="button left" href="#" title="Start/Stop">&#x25B6;</a>
    <a id="browserponies-plus" class="button middle" href="#" title="Add">+</a>
    <a id="browserponies-minus" class="button right" href="#" title="Remove">-</a>
    <div style="clear: both;"></div>
</div>
<div id="browserponies-suggest"></div>
HERE;
        $action->raw($buttons);
    }

}
?>
