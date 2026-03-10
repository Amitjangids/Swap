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


// $(document).ready(function(){
//   const header = document.querySelector("header");
//   const toggleClass = "is-sticky";

//   window.addEventListener("scroll", () => {
//     const currentScroll = window.pageYOffset;
//     if (currentScroll > 50) {
//       header.classList.add(toggleClass);
//     } else {
//       header.classList.remove(toggleClass);
//     }
//   });
// });



