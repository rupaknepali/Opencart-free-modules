(function ($) {
  $(document).ready(function () {
    //$('#cart').hide();
    $(window).scroll(function () {
      if ($(document).scrollTop() > 100) {
        $('#cart')
          .fadeIn('slow')
          .css({ position: 'fixed', right: '0px', top: '50vh' });
        $('#cart-total').fadeOut('slow');
      } else {
        $('#cart')
          .fadeIn('slow')
          .css({ position: 'relative', right: '0px', top: '0px' });
        $('#cart-total').fadeIn('slow');
      }
    });
  });
})(jQuery);
