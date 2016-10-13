var setRem = function( doc, win ){
	var docEl = doc.documentElement,
		resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
		recalc = function () {
			var clientWidth = docEl.clientWidth;
			if (!clientWidth) return;
			docEl.style.fontSize = 20 * (clientWidth / 320) + 'px';
		};

	if (!doc.addEventListener) return;
	win.addEventListener(resizeEvt, recalc, false);
	doc.addEventListener('DOMContentLoaded', recalc, false);
}

setRem( document, window );

window.onresize = (function( doc, win ){
	setRem( doc, win );
})( document, window );

$(function(){
	var search = $('.search-box'),
		divBox = search.find('div');
	search.on('click','li',function(){
		var index = $(this).index();
		divBox.hide().eq(index).show();
		$('.mark').show();
	});

	(function(){
		var w = document.body.clientWidth;
		$('.loop').each(function(){
			var _this = $(this),
				listBox = _this.find('.img-list'),
				pageInfo = _this.find('.page-list'),
				pageLine = _this.find('.line'),
				imgList = listBox.find('img'),
				len = imgList.length;
			_this.attr('w',w).attr('num',0).attr('max',len);
			listBox.css({width: w* len});
			if( pageInfo.get(0) ){
				var iList = [];
				$.each( imgList, function( n ){
					if( !n ){
						iList.push('<i class="on"></i>');
					}else{
						iList.push('<i></i>');
					}
				});
				pageInfo.html( iList.join('') );
			}
			if( pageLine.get(0) ){
				pageLine.find('span').text('1/'+len);
			}
		});
		loop();
	})();

	function loop(){
		var loopBox = $(".loop");
		var proBox = loopBox.find('.img-list');
		var loading = true;
		var _startY = 0;
		var _startX = 0;
		var _moveX = 0;
		var _moveY = 0;
		var _absMoveX = 0;
		var _absMoveY = 0;
		var distance = 90;
		var __isScr = true;
		var thisLoopBox;
		var _move = Number( loopBox.attr('w') );
		var _index = Number( loopBox.attr('num') );
		var _max = Number( loopBox.attr('max') );
		var pageInfo;
		var pageLine;
		var _pageInfo;
		var _this;
		// °ó¶¨²ËÆ·ÇøÓò
		proBox.on('touchstart',function( e ){
			if( loading ){
				fnTouches( e );
				fnTouchstart( e );
			}
		});
		proBox.on('touchmove',function( e ){
			if( loading){
				fnTouches( e );
				fnTouchmove( e );
			}
		});
		proBox.on('touchend',function(  ){
			_this = $(this);
			if( loading ){
				fnTouchend( $( this ) );
			}
		});
		// touches
		function fnTouches( e ){
			if(!e.touches){
				e.touches = e.originalEvent.touches;
			}
		}
		// touchstart
		function fnTouchstart( e ){
			_startX = e.touches[0].pageX;
			_startY = e.touches[0].pageY;
		}
		// touchmove
		function fnTouchmove( e ){
			_curX = e.touches[0].pageX;
			_curY = e.touches[0].pageY;
			_moveX = _curX - _startX;
			_moveY = _curY - _startY;
		}
		// touchend
		function fnTouchend( _this ){
			_absMoveX = Math.abs( _moveX );
			thisLoopBox = _this.parent('.loop');
			_move = Number( thisLoopBox.attr('w') );
			_index = Number( thisLoopBox.attr('num') );
			_max = Number( thisLoopBox.attr('max') );
			_pageInfo = thisLoopBox.find('.page-list i');
			pageInfo = thisLoopBox.find('.page-list');
			pageLine = thisLoopBox.find('.line');
			if( __isScr && _absMoveX > distance ){
				if( _moveX < 0 ){
					move( 'l', _this );
				}else{
					move( 'r', _this );
				}
				loading = false;
			}
			_startY = 0;
			_startX = 0;
			_moveX = 0;
			_moveY = 0;
			_absMoveX = 0;
			_absMoveY = 0;
		}
		function move( type, _this ){
			if( type === 'l' ){
				if( _index > _max-2 ){
					_index = _max-1;
				}else{
					_index++;
				}
				moveValue = -( _index *_move );
			}else{
				if( _index < 1 ){
					_index = 0;
				}else{
					_index--;
				}
				moveValue = -(_index * _move);
			}
			_this.animate({
				left: moveValue
			}, 500,function(){
				loading = true;
				if( pageInfo.get(0) ){
					_pageInfo.removeClass('on');
					_pageInfo.eq(_index).addClass('on');
				}
				if( pageLine.get(0) ){
					pageLine.find('span').text((_index+1)+'/'+_max);
				}
				thisLoopBox.attr( 'num' , _index );
			} );
		}
	}
});