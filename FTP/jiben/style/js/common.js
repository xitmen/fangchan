// JavaScript Document

//自定义筛选器菜单生成  by：xhorz 2014-03-28
	var bs={
		id:0,
		od:null,
		sc:function(o){
			var n=$(".subm .c_"+o).children("ul").get(0);
			if(bs.od==null)
				bs.od=n;
			else
				$(bs.od).hide();
			$(n).show();
			bs.od=n;
		},
		mk:function(a){
			var r="<ul>\r\n";
			var s="";
			for(var i=0;i<a.length;i++)
			{
				if(a[i].c!=null){
					s=bs.mk(a[i].c);
					r+="<li class='c_"+bs.id+"'><a href='javascript:bs.sc("+bs.id+");'>"+a[i].n+"</a>\r\n"+s+"\r\n</li>\r\n";
				} else {
					r+="<li class='c_"+bs.id+"'><a href='"+a[i].e+"'>"+a[i].n+"</a></li>\r\n";
				}
				bs.id++;
			}
			r=r+"</ul>";
			return r;
		}
	};
	//显示商品查询筛选菜单
	function showMenu(){
		$(".mb").height($(".p").height()).show();
		$(".sub").show().slideDown();
	}
	//商品详情选项卡切换
	function sel(index){
		if(osel==null)
			index=1;
		else
			$(osel).toggleClass("on");
		osel=$("#m>.m ul li:nth-child("+index+") a").get(0);
		if(osel==null){alert(1); return;}
		$(osel).toggleClass("on");
	}	
	//商品订购数量计数函数
	function prodCnt(action,input){
		var p=$(input).parent().find("input");
		var cnt=p.val();
		cnt=parseInt(cnt);
		if(action=='add'){
			cnt++;
			p.val(cnt);
			if(cnt>1){
				p.parent().find("a.minus").css("color","#333");
			}
		}
		if(action=='minus'){
			if(cnt>1)
				p.val(--cnt);		
			if(cnt-1<=0){
				p.parent().find("a.minus").css("color","#ccc");
			}
		}
		return cnt;
	}