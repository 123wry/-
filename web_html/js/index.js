$("#emoji").click(function(){
    var display = $(".emoji").css("display");
    if(display == 'none'){
        $(".emoji").css("display","block");
    } else {
        $(".emoji").css("display","none");
    }
});
$(".emoji a").click(function(){
    var htl = $(".talk_write").html();
    htl = htl + $(this).html();
    $(".talk_write").html(htl);
});

// 滚动到聊天底部
function scrollBottom()
{
    var height = $(".talk_middle_content").prop('scrollHeight');
    $(".talk_middle_content").scrollTop(height);
}
$(function(){
    // 聊天对象列表
    $.ajax({
        method:"post",
        url:domain+"/Index/queueList",
        data:{},
        success:function(ret){
            htl = "";
            var data = ret['data'];
            for(var i in data){
                htl += '<li date-id="'+data[i]['talk_id']+'" data-target="'+data[i]['queue_id']+'" class="talk_obj">';
                if(data[i]['unread'] != 0){
                    htl += '<p class="unread_tag">'+data[i]['unread']+'</p>';
                } else {
                    htl += '<p class="unread_tag_none">'+data[i]['unread']+'</p>';
                }
                var status = '';
                if(data[i]['status'] == 1){
                    status = '在线';
                } else{
                    status = '离线';
                }
                htl +=
                '<a class="talk_name">'+data[i]['email']+'</a>'+
                '<p class="last_talk">'+data[i]['msg']+'</p>'+
                '<p class="last_time">'+data[i]['last_time']+'</p>'+
                '<p class="now_status">['+status+']</p>'+
                '</li>';
            }
            $(".add_friend").after(htl);
            

        },
        error:function(e){
            $(".alter_tab .alter_content").html("网络异常");
            $(".alter").css("display","block");
        }
    });

    $.ajax({
        method:"post",
        url:domain+"/Index/getStatus",
        data:{},
        success:function(ret){
            if(ret.code == 200){
                data = ret.data;
                $(".examine_tag").html(data['count']);
                $(".user_email").html(data['email']);
            }
        },
        error:function(e){
            $(".alter_tab .alter_content").html("网络异常");
            $(".alter").css("display","block");
        }
    });
});

// 添加朋友
$(".add_friend button").click(function(){
    var friend_email = $("#friend_email").val();
    $.ajax({
        method:"post",
        url:domain+"/Index/addFriend",
        data:{email:friend_email},
        success:function(ret){
            $(".alter_tab .alter_content").html(ret.message);
            $(".alter").css("display","block");
            
        },
        error:function(e){
            $(".alter_tab .alter_content").html("网络异常");
            $(".alter").css("display","block");
        }
    });
});
// 添加朋友列表
$(".friend_list").click(function(){
    $.ajax({
        method:"post",
        url:domain+"/Index/getFriend",
        data:{},
        success:function(ret){
            if(ret.code != 200){
                $(".alter_tab .alter_content").html(ret.message);
                $(".alter").css("display","block");
            }else {
                var data = ret['data'];
                var htl = "";
                for (var i in data){
                    htl += "<li>";
                    htl += "<p class='friend_email'>"+data[i]['email']+"</p>";
                    htl += "<p class='friend_time'>"+data[i]['ctime']+"</p>";
                    if(data[i]['status'] == 1 || data[i]['status'] == 2){
                        htl += "<button class='friend_ok' disabled>同意</button>";
                        htl += "<button class='friend_no' disabled>拒绝</button>";
                    } else {
                        htl += "<button data-id="+data[i]['examine_id']+" data-target='1' class='friend_ok'>同意</button>";
                        htl += "<button data-id="+data[i]['examine_id']+" data-target='2' class='friend_no'>拒绝</button>";
                    }
                    htl += "</li>"
                }
                $(".model_tab .model_content").html(htl);
                $(".model").css("display","block");
            }
        },
        error:function(e){
            $(".alter_tab .alter_content").html("网络异常");
            $(".alter").css("display","block");
        }
    });
});
// 同意或者拒绝添加朋友
$(document).on("click",".friend_ok,.friend_no",function(){
    var status = $(this).attr("data-target");
    var examine_id = $(this).attr("data-id");
    $.ajax({
        method:"post",
        url:domain+"/Index/friendStatus",
        data:{status:status,examine_id:examine_id},
        success:function(ret){
            if(ret.code == 200){
                window.location.reload();
            }else {
                $(".model").css("display","none");
                $(".alter_tab .alter_content").html(ret.message);
                $(".alter").css("display","block");
            }
        },
        error:function(e){
            $(".model").css("display","none");
            $(".alter_tab .alter_content").html("网络异常");
            $(".alter").css("display","block");
        }
    });
});
// 点击显示聊天记录
$(document).on("click",".talk_obj",function(){
    $(".talk_obj").css("background-color","white");
    $(this).css("background-color","#eeeeee");
    var queue_id = $(this).attr("data-target");
    localStorage.setItem("queue_id",queue_id);
    $.ajax({
        method:"post",
        url:domain+"/Index/readed",
        data:{queue_id:queue_id},
        success:function(ret){
            if(ret.code == 200){
                $(".talk_obj").each(function(){
                    var _this = $(this);
                    var qid = _this.attr("data-target");
                    if(qid == queue_id){
                        _this.children(".unread_tag").html(0);
                        _this.children(".unread_tag").addClass("unread_tag_none");
                        _this.children(".unread_tag_none").removeClass("unread_tag");
                    }
                });
            }
        },
        error:function(e){
            $(".alter_tab .alter_content").html("网络异常");
            $(".alter").css("display","block");
        }
    })
    $.ajax({
        method:"post",
        url:domain+"/Index/getMsg",
        data:{queue_id:queue_id},
        success:function(ret){

            htl = "";
            var data = ret['data']['data'];
            var uid = ret['data']['uid'];
            for(var i in data){
                if(data[i]['user_id'] == uid){
                    htl += '<div class="talk_right"><div>'+data[i]['msg']+'</div><img src="img/1.jpg"/></div>';
                } else {
                    htl += '<div class="talk_left"><img src="img/1.jpg"/><div>'+data[i]['msg']+'</div></div>';
                }
            }
            $(".talk_middle_content").html(htl);
            scrollBottom();
            
            

        },
        error:function(e){
            $(".alter_tab .alter_content").html("网络异常");
            $(".alter").css("display","block");
        }
    });
});
// 发送聊天
$(".talk_send button").click(function(){
    var txt = $(".talk_write").html();
    var queue_id = localStorage.getItem("queue_id");
    $.ajax({
        method:"post",
        url:domain+"/Index/sendMsg",
        data:{queue_id:queue_id,msg:txt},
        success:function(ret){
            if(ret.code ==200){
                var htl = '<div class="talk_right"><div>'+txt+'</div><img src="img/1.jpg"/></div>';
                $(".talk_middle_content").append(htl);
                scrollBottom();
                $(".talk_write").html("");

                $(".talk_obj").each(function(){
                    var _this = $(this);
                    var qid = _this.attr("data-target");
                    if(qid == queue_id){
            
                        _this.children(".last_talk").html(txt);
                        _this.children(".last_time").html('刚刚');
                        
                    }
                });

                var data = ret.data;
                var talk = JSON.stringify({"type":2,"queue_id":data['queue_id'],"user_id":data['user_id'],"talk_id":data['talk_id'],"msg":txt});
                websocket.send(talk);

               

            } else {
                $(".alter_tab .alter_content").html(ret.message);
                $(".alter").css("display","block");
            }
        },
        error:function(e){
            $(".alter_tab .alter_content").html("网络异常");
            $(".alter").css("display","block");
        }
    })
});

window.setInterval(function(){
    var uid = localStorage.getItem("headermustneed");
    var len = uid.length;
    var lens = len-16;
    var id = uid.substr(10,lens);
    var ping = {"type":"ping",'id':id};
    
    websocket.send(JSON.stringify(ping));
},10000);

websocket.onopen = function (evt) {
    var uid = localStorage.getItem("headermustneed");
    var len = uid.length;
    var lens = len-16;
    var id = uid.substr(10,lens);
    var data = JSON.stringify({"type":1,'id':id});
    $.ajax({
        method:"post",
        url:domain+"/Index/onConnect",
        data:{status:1},
        success:function(ret){
            if(ret.code != 200){
                $(".alter_tab .alter_content").html(ret.message);
                $(".alter").css("display","block");
            }
        },
        error:function(e){
            $(".alter_tab .alter_content").html("网络异常");
            $(".alter").css("display","block");
        }
    });
    websocket.send(data);
};
websocket.onmessage = function (evt) {

    var data = evt.data;
    var msg = JSON.parse(data);

    if(msg['type'] == 3){
        $(".talk_obj").each(function(){
            var _this = $(this);
            var id = _this.attr("date-id");
            if(id == msg['id']){
                _this.children(".now_status").html("[在线]");
            }
        });
        return 0;
    }
    if(msg['type'] == 4){
        $(".talk_obj").each(function(){
            var _this = $(this);
            var id = _this.attr("date-id");
            if(id == msg['id']){
                _this.children(".now_status").html("[离线]");
            }
        });
        return 0;
    }

    qid = localStorage.getItem("queue_id");
    
    $(".talk_obj").each(function(){
        var _this = $(this);
        var qid = _this.attr("data-target");
        if(qid == msg['queue_id']){

            _this.children(".last_talk").html(msg['msg']);
            _this.children(".last_time").html('刚刚');

            // 如果和自己对话不需要已读未读
            if(msg['user_id'] != msg['talk_id']){

                var len = _this.children(".unread_tag_none").length;
                if(len > 0){
                    _this.children(".unread_tag_none").addClass("unread_tag");
                    _this.children(".unread_tag_none").removeClass("unread_tag_none");
                    _this.children(".unread_tag").html(1);
                } else {
                    var unread = _this.children(".unread_tag").html();
                    unread = parseInt(unread) +1;
                    _this.children(".unread_tag").html(unread)
                }

            }
            
            var htl = _this[0].outerHTML;
            $(".add_friend").after(htl);
            _this.remove();
            
        }
    });

    if(qid == msg['queue_id'] && msg['user_id'] != msg['talk_id']){
        htl = '<div class="talk_left"><img src="img/1.jpg"/><div>'+msg['msg']+'</div></div>';
        $(".talk_middle_content").append(htl);
        scrollBottom();
    }

};
websocket.onclose = function(evt){
    
}