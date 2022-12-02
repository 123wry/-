<meta http-equiv='Content-Type' content='text/html' charset='utf-8' >
<title>Hello World</title>
<link href='public/public.css?version=25' rel='stylesheet'/>
<script src="js/jquery.min.js"></script>
<?php require_once "public/model.php"?>
<?php require_once "public/alter.php"?>
<script>
    localStorage.setItem("queue_id",0);
    $.ajaxSetup({
        headers:{uid:localStorage.getItem("headermustneed")},
        complete:function(xhr){
            ret = xhr.responseJSON;
            if(ret.code == 401){
                window.location.href='login.php';
            }
        }
    });
    var wsServer = 'ws://127.0.0.1:9060';
    var websocket = new WebSocket(wsServer);
    var domain = "http://a.person.com";
</script>