/**
 * Created by A.J on 2017/7/13.
 */
$(document).ready(function(){
    var editor1;
    KindEditor.ready(function(K) {
        editor1 = K.create('textarea[name="neirong"]', {
            allowFileManager : true,
            width : '100%',
            height : '500px',
            filterMode : false,
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
    String.prototype.stripHTML = function() {
        var reTag = /\s+/g;
        var zhongjian = this.replace(reTag,' ');
        var reTag = /<(?:.|\s)*?>|(^\s+)|(\s+$)|(\n+)|(\r+)|(\s{2,})/g;
        return zhongjian.replace(reTag,"");
    };
    $.extend({zhaiyao:function(){
        if($("#zhaiyao").length > 0 && $("#zhaiyao").val() == ''){
            var wenben = editor1.text().stripHTML();
            if(wenben.length > 500){
                $("#zhaiyao").val(wenben.substr(0,500)+'...');
            }
            else{
                $("#zhaiyao").val(wenben);
            }
        }
    }});
    //保存
    $("#baocun").click(function(){
        if($.catfish()){
            editor1.sync();
            $.zhaiyao();
        }
    });
});