function timecycle()
{
var time = new Date();

var hour = time.getHours();


if (hour>=6 && hour<=9)
  {
    jQuery(function($) {
        $("body").css({'background-image' : 'url(bluegrade.jpg)'});
    });
  }
else if (hour >=9 && hour<=17)
  {
    jQuery(function($) {
        $("body").css({'background-image' : 'url(bluegrade.jpg)'});
    });
  }
else if (hour >=17 && hour<=19)
  {
    jQuery(function($) {
        $("body").css({'background-image' : 'url(dawn.jpg)'});
    });
  }
  else if (hour >=19)
  {
    jQuery(function($) {
        $("body").css({'background-image' : 'url(night.jpg)'});
    });
  }
  else if (hour >=0 && hour<=6)
  {
    jQuery(function($) {
        $("body").css({'background-image' : 'url(dawn.jpg)'});
    });
  }
else
  {
    jQuery(function($) {
        $("body").css({'background-image' : 'url(bluegrade.jpg)'});
    });
  }
}
