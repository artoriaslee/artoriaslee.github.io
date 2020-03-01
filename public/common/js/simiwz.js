/**
 * Created by A.J on 2017/10/15.
 */
$(document).ready(function(){
    $("#fromdatetime, #todatetime").datetimepicker({format: 'yyyy-mm-dd hh:ii:ss'});
    $('table a.twitter').confirm({
        title: $('#quedingshanchu').text(),
        content: $('#fangruhuishouzhan').text(),
        confirmButton: $('#jixu').text(),
        cancelButton: $('#quxiao').text(),
        onAction: function(action){
            if(action == 'confirm'){
                var obj = this.$target;
                $.post("recycleArticle", { id: this.$target.parent().parent().children(":eq(0)").children("input").val()},
                    function(data){
                        obj.parent().parent().remove();
                    });
            }
        }
    });
    $('table a.gongkai').confirm({
        title: $('#quedinggongkai').text(),
        content: $('#fangrugongkai').text(),
        confirmButton: $('#jixu').text(),
        cancelButton: $('#quxiao').text(),
        onAction: function(action){
            if(action == 'confirm'){
                var obj = this.$target;
                $.post("gongkaiArticle", { id: this.$target.parent().parent().children(":eq(0)").children("input").val()},
                    function(data){
                        obj.parent().parent().remove();
                    });
            }
        }
    });

    $("#zxuan").click(function(){
        if($(this).prop("checked")){
            $(".gouxuan").prop("checked",true);
        }
        else{
            $(".gouxuan").prop("checked",false);
        }
    });
    $("#pgongkai").click(function(){
        $.caozuo($(this),'pgongkai');
    });
    $("#pshanchu").click(function(){
        $.confirm({
            title: $('#quedingshanchu').text(),
            content: $('#wenzhanghuishou').text(),
            confirmButton: $('#jixu').text(),
            cancelButton: $('#quxiao').text(),
            confirm: function(){
                $.caozuo($(this),'pshanchu');
            }
        });
    });
});
$.extend({'caozuo':function(obj,cz){
    var zcuan = '',ind = new Array();
    $(".gouxuan").each(function(index,element){
        if($(this).prop("checked")){
            ind.unshift(index);
            if(zcuan == ''){
                zcuan = $(this).val();
            }
            else{
                zcuan += ',' + $(this).val();
            }
        }
    });
    if(zcuan != ''){
        obj.children("span").removeClass("hidden");
        $.post("modifysimi", { zcuan: zcuan, cz: cz},
            function(data){
                obj.children("span").addClass("hidden");
                $.each(ind, function(i, value) {
                    switch(cz){
                        case 'pgongkai':
                            $(".gouxuan:eq("+value+")").parent().parent().remove();
                            break;
                        case 'pshanchu':
                            $(".gouxuan:eq("+value+")").parent().parent().remove();
                            break;
                    }
                });
            });
    }
    else{
        $.alert({
            title: $('#jinggao').text(),
            content: $('#zhishaoxuanyixiang').text(),
            confirmButton: $('#queding').text()
        });
    }
}});