/*
	SuperBox v1.0.0 (modified by bootstraphunter.com)
	by Todd Motto: http://www.toddmotto.com
	Latest version: https://github.com/toddmotto/superbox

	Copyright 2013 Todd Motto
	Licensed under the MIT license
	http://www.opensource.org/licenses/mit-license.php

	SuperBox, the lightbox reimagined. Fully responsive HTML5 image galleries.
*/
;(function($) {

	$.fn.SuperBox = function(options) {

		var superbox      = $('<div class="superbox-show"></div>'),
			superboximg   = $('<img src="" class="superbox-current-img"><div id="imgInfoBox" class="superbox-imageinfo inline-block"> <h1>Image Title</h1><span><p><em>http://imagelink.com/thisimage.jpg</em></p><p class="img-size"><em>0B</em></p><div class="superbox-img-description">Image description</div><p><a href="javascript:void(0);" class="btn btn-primary btn-sm edit_image">Edit Image</a> <a href="javascript:void(0);" class="btn btn-danger btn-sm delete_image">Delete</a></p></span> </div>'),
			superboxclose = $('<div class="superbox-close txt-color-white"><i class="fa fa-times fa-lg"></i></div>');

		superbox.append(superboximg).append(superboxclose);

		var imgInfoBox = $('.superbox-imageinfo');

		return this.each(function() {

			$('.superbox-list').click(function() {
				$this = $(this);

				var currentimg = $this.find('.superbox-img'),
					imgData = currentimg.data('img'),
					imgDescription = currentimg.attr('alt') || "",
					imgLink = imgData,
					imgTitle = currentimg.attr('title') || "",
					imgDelete = currentimg.attr('delete') || "javascript:void(0);",
					imgEdit = currentimg.attr('edit') || "javascript:void(0);",
					imgSize = currentimg.attr('size') || "No Size";



				superboximg.attr('src', imgData);

				$('.superbox-list').removeClass('active');
				$this.addClass('active');



				superboximg.find('em').text(imgLink);
				superboximg.find('h1').text(imgTitle);
				superboximg.find('.superbox-img-description').html(imgDescription);
				superboximg.find('.delete_image').attr('href',imgDelete);
				superboximg.find('.edit_image').attr('href',imgEdit);
				superboximg.find('.img-size').html(imgSize);

				//console.log("fierd")

				if($('.superbox-current-img').css('opacity') == 0) {
					$('.superbox-current-img').animate({opacity: 1});
				}

				if ($(this).next().hasClass('superbox-show')) {
					$('.superbox-list').removeClass('active');
					superbox.toggle();
				} else {
					superbox.insertAfter(this).css('display', 'block');
					$this.addClass('active');
				}

				$('html, body').animate({
					scrollTop:superbox.position().top - currentimg.width()
				}, 'medium');

			});

			$('.superbox').on('click', '.superbox-close', function() {
				$('.superbox-list').removeClass('active');
				$('.superbox-current-img').animate({opacity: 0}, 200, function() {
					$('.superbox-show').slideUp();
				});
			});

		});
	};
})(jQuery);