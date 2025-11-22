document.addEventListener('DOMContentLoaded', function () {
  var scrollSpy = new bootstrap.ScrollSpy(document.body, {
      target: '#navbar-example',
      offset: 54 // Adjust as necessary
  });
});

// Self-invoking function for jQuery
(function($) {
  "use strict";

  // Smooth scrolling
  $('a.js-scroll-trigger[href*="#"]:not([href="#"])').click(function() {
      if (location.pathname.replace(/^\//, '') === this.pathname.replace(/^\//, '') && location.hostname === this.hostname) {
          var target = $(this.hash);
          target = target.length ? target : $('[name=' + this.hash.slice(1) + ']');
          if (target.length) {
              $('html, body').animate({
                  scrollTop: (target.offset().top - 48)
              }, 1000, "easeInOutExpo");
              return false;
          }
      }
  });

  // Collapse navbar on click
  $('.js-scroll-trigger').click(function() {
      $('.navbar-collapse').collapse('hide');
  });

  // Navbar collapse function
  var navbarCollapse = function() {
      var navElement = $("#mainNav");
      if (navElement.length > 0) { // Check if the element exists
          if (navElement.offset().top > 100) {
              navElement.addClass("navbar-shrink");
          } else {
              navElement.removeClass("navbar-shrink");
          }
      }
  };

  // Initial check
  navbarCollapse();

  // Bind scroll event
  $(window).scroll(navbarCollapse);

})(jQuery);

// Google Maps initialization
var locations = [[50.048914, 21.981690]];

function initMap() {
  var map = new google.maps.Map(document.getElementById('map'), {
      zoom: 15,
      zoomControl: true,
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      mapTypeControl: false,
      styles: [{
          stylers: [
              { hue: '#cacaca' },
              { saturation: -100 },
              { lightness: 10 }
          ]
      }],
      center: new google.maps.LatLng(50.048914, 21.981690)
  });

  var infowindow = new google.maps.InfoWindow();

  for (var i = 0; i < locations.length; i++) {
      var marker = new google.maps.Marker({
          position: new google.maps.LatLng(locations[i][0], locations[i][1]),
          map: map
      });

      // Add click listener for marker
      google.maps.event.addListener(marker, 'click', (function(marker, i) {
          return function() {
              infowindow.setContent("Location: " + locations[i][0]);
              infowindow.open(map, marker);
          }
      })(marker, i));
  }
}

// Ensure the initMap function is called after Google Maps is loaded
window.initMap = initMap;

// Owl Carousel initialization
$(document).ready(function() {
  $(".owl-carousel").owlCarousel({
      loop: true,
      items: 1,
      autoplay: true
  });
});