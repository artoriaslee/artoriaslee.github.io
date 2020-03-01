/**
 * Created by A.J on 2016/10/14.
 */
$(document).ready(function(){
    if($("#zhengwen").length > 0 && $("#catfish").length > 0){
        var editor1;
        KindEditor.ready(function(K) {
            editor1 = K.create('textarea[id="zhengwen"]', {
                allowFileManager : true,
                width : '100%',
                height : '200px',
                items : ["undo", "redo", "|", "forecolor", "hilitecolor", "bold", "italic", "underline", "strikethrough", "|", "emoticons"],
                cssData: 'body {font-family: "微软雅黑"; font-size: 16px}',
                afterCreate : function() {
                    var self = this;
                    K.ctrl(document, 13, function() {
                        self.sync();
                        $.zhaiyao();
                        K('form[name=writeForm]')[0].submit();
                    });
                    K.ctrl(self.edit.doc, 13, function() {
                        self.sync();
                        $.zhaiyao();
                        K('form[name=writeForm]')[0].submit();
                    });
                }
            });
            prettyPrint();
        });
    }
    $("#pinglun").click(function(){
        editor1.sync();
        var obj = $(this);
        if(obj.children("span:eq(1)").hasClass("hidden") && $("#catfish").length > 0){
            if($.trim($("#zhengwen").val()) == '')
            {
                alert($("#meipinglun").text());
                return false;
            }
            obj.children("span:eq(0)").removeClass("hidden");
            obj.children("span:eq(1)").addClass("hidden");
            $.post($("#webroot").text()+"index/Index/pinglun", { id: $(this).prev().val(), pinglun: $("#zhengwen").val() },
                function(data){
                    obj.children("span:eq(0)").addClass("hidden");
                    obj.children("span:eq(1)").removeClass("hidden");
                });
        }
        else
        {
            alert($("#yipinglun").text());
        }
    });
    var zan = false;
    $("#zan").click(function(){
        if(zan == false && $("#catfish").length > 0){
            $.post($("#webroot").text()+"index/Index/zan", { id: $(this).prev().val() },
                function(data){
                    $("#zanshu").text(parseInt($("#zanshu").text())+1);
                    zan = true;
                });
        }
        else{
            alert($("#zanyici").text());
        }
    });
    $("#shoucang").click(function(){
        if($("#yishoucang").hasClass("hidden") && $("#catfish").length > 0){
            $.post($("#webroot").text()+"index/Index/shoucang", { id: $(this).parent().parent().children("input:first").val() },
                function(data){
                    $("#yishoucang").removeClass("hidden");
                });
        }
        else
        {
            alert($("#yijingsc").text());
        }
    });
});