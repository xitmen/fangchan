	//计算总价格
	function calcTotail(){
		var tp=0.0;
		var p=0.0;
		var c=0;
		$("input.ck_child").each(function(){
				if(this.checked){
					p=parseFloat($(this).attr("itemprice"));
					c=parseInt($(this).attr("itemcount"));
					tp=tp+(p*c);
				}
			}
		);
		if(tp>0){
			$("#dw").show();
			$("#m").css("paddingBottom","5.2em");
		}
		else{
			$("#dw").hide();
			$("#m").css("paddingBottom","0");			
		}
		tp=Math.round(tp*100)/100;
		$("#dw .pr>b").text(parseFloat(tp));
	}
	$(document).ready(function(){
		//购买数量计数按钮事件
		$("a.minus").bind("click",function(){
			var cnt=prodCnt('minus',this);
			$(this).parent().parent().parent().find(".ck_child").each(function(){this.setAttribute("itemcount",cnt)});
			calcTotail();
		});
		$("a.add").bind("click",function(){
			var cnt=prodCnt('add',this);
			$(this).parent().parent().parent().find(".ck_child").each(function(){this.setAttribute("itemcount",cnt)});
			calcTotail();
		});
		//商品计价选件
		$("input.ck_child").bind("click",function(){
			calcTotail();
		});
		//商品计价全选
		$("input.all").bind("click",function(){
			if(this.checked==true){
				$("input.ck_child").each(
					function(){this.checked=true;}
				);
			} else {
				$("input.ck_child").each(
					function(){this.checked=false;}
				);
			}
			calcTotail();
		});
	});