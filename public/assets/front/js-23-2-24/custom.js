$(document).ready(function(){
    $('.toggle-menu').click(function(){
      $('.mobile-header-menu-wrapper').addClass('active');
      $('.black-layer').addClass('active');
      $('.close-icon').addClass('active');
      $('body').addClass('overflow-off');
      $('html').addClass('overflow-off');
    });
    $('.close-icon').click(function(){
      $('.black-layer').removeClass('active');
      $('.mobile-header-menu-wrapper').removeClass('active');
      $('body').removeClass('overflow-off');
      $('html').removeClass('overflow-off');
    });
    $('.black-layer').click(function(){
      $(this).removeClass('active');
      $('.mobile-header-menu-wrapper').removeClass('active');
      $('.close-icon').removeClass('active');
      $('body').removeClass('overflow-off');
      $('html').removeClass('overflow-off');
    });
});





