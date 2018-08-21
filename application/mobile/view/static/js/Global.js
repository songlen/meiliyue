let Global = (function () {

    function openLoading(msg = "处理中") {
        if ($(".msgWrap").length == 0) {
            $("body").append($('<div class="msgWrap"></div>'));
        }
        if ($(".mLoading").length == 0) {
            $("body").append($(`<div class="mLoading">${msg}</div>`));
        }
    }

    function closeLoading(resultMsg, callback = function () {}, delay = 500) {
        $(".mLoading").html(resultMsg);
        setTimeout(function () {
            $(".mLoadingMask")
                .add($(".mLoading")).remove();
            callback();
        }, delay);
    }

    function messageWin(msg) {
        if ($(".msgWrap").length == 0) {
            let $msgDiv = $(
                `
                <div class="msgWrap">
                    <div class="msg">
                        <p class="msgText">${msg}</p>
                        <p class="msgCtrl">
                            <span class="closeMsg">确定</span>
                        </p>
                    </div>
                </div>
            `
            );
            $msgDiv.find(".closeMsg").click(function () {
                $msgDiv.remove();
            });
            $("body").append($msgDiv);
        }
    }

    function messageConfirWin(msg, callback) {
        if ($(".msgWrap").length == 0) {
            let $msgDiv = $(
                `
                <div class="msgWrap">
                    <div class="msg">
                        <p class="msgText">${msg}</p>
                        <p class="msgCtrl">
                            <span class="closeMsg">取消</span>
                            <span class="gotoCallback">确定</span>
                        </p>
                    </div>
                </div>
            `
            );
            $msgDiv.find(".closeMsg").click(function () {
                $msgDiv.remove();
            });
            $msgDiv.find(".gotoCallback").click(function () {
                callback();
                $msgDiv.remove();
            });
            $("body").append($msgDiv);
        }
    }

    function bindLimitCount(element, textCount, showElement) {
        element.onchange = function () {
            limitCount(this)
        }
        element.onkeydown = function () {
            limitCount(this)
        }
        element.onkeyup = function () {
            limitCount(this)
        }

        function limitCount(el) {
            el.value = el.value.substring(0, textCount)
            let count = el.value.length
            if (showElement) {
                showElement.innerHTML = count
            }
            if (count >= textCount) {
                messageWin(`最多输入${textCount}个字符`);
            }
        }
    }

    function stampToDate(timestamp) {
        var date = new Date(timestamp.toString().length == 10 ? (timestamp * 1000) : (timestamp * 1)); //时间戳为10位需*1000，时间戳为13位的话不需乘1000
        Y = date.getFullYear() + '-';
        M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-';
        D = (date.getDate() < 10 ? "0" + date.getDate() : date.getDate()) + ' ';
        h = (date.getHours() < 10 ? "0" + date.getHours() : date.getHours()) + ':';
        m = (date.getMinutes() < 10 ? "0" + date.getMinutes() : date.getMinutes());
        // s = date.getSeconds();
        return Y + M + D + h + m;
    }

    function stampToStr(stamp) { //传来的stamp十位数
        if (!stamp) return '';

        let nowStamp = Math.round((new Date().getTime()) / 1000);
        let howLong = nowStamp - stamp;

        if (howLong < 60) { //1分钟内
            return "1分钟内";
        } else if (howLong < 3600) { //1小时内，显示分钟数
            let minutes = Math.floor(howLong / 60);
            return minutes + "分钟前";
        } else if (howLong < 43200) { //12小时内，显示小时数
            let hours = Math.floor(howLong / 3600);
            return hours + "小时前";
        } else if (howLong >= 43200 && howLong < 86400) { //大于12小时，小于24小时
            let dateStr = stampToDate(stamp);
            console.log(dateStr)
            let minStr = dateStr.substr(dateStr.length - 5);
            return "昨天 " + minStr;
        } else if (howLong > 86400) { //大于24小时，直接显示时间
            return stampToDate(stamp);
        }
    }

    function isIOS() {
        let u = navigator.userAgent;
        let isIOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
        return isIOS;
    }

    function gotoApp(funcStr) { //调原生方法
        // let u = navigator.userAgent;
        // let isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端


        let isIOS = isIOS();
        if (isIOS) {
            //
        } else { //安卓
            window.Android[funcStr]();
        }
    }

    function eleCantClick(ele) {
        $(ele).addClass("eventsDisabled");
    }

    function eleCanClick(ele) {
        $(ele).removeClass("eventsDisabled");
    }

    function fullScreen(ele) {
        let $fullScreen = $(
            `
            <div class="fullScreen">
                <div class="videoHeader">
                    <div class="closeFullScreen"></div>
                </div>

                <div class="fullScreenScroll">
                    <div class="fullScreenWrap">
                        <video id="video1" width="100%" height="100%" src=${$(ele).attr("src")} autoplay loop></video>
                    </div>
                </div>
            </div>
            `
        );

        //点击关闭事件
        $fullScreen.find(".closeFullScreen").click(function () {
            $fullScreen.remove();
        });

        $("body").append($fullScreen);
    }

    //暴露的接口------------------------
    return {
        //值
        host: "http://meiliyue.caapa.org",
        //方法
        openLoading, //msg
        closeLoading, //msg:完成瞬间显示的文字;callback;delay:人工设定延迟
        messageWin, //msg
        messageConfirWin, //msg;callback：点击确定的回调
        bindLimitCount, //element:textarea元素;textCount:限制的字数;showElement:实时显示字数的元素  注:没有表情插件的
        stampToDate, //stamp  注:10位或13位都可以
        stampToStr, //stamp:10位时间戳
        isIOS, //判断是不是ios系统
        gotoApp,
        eleCantClick, //让元素无法点击 ele:元素
        eleCanClick, //ele:元素
        fullScreen // 预览全屏视频 ele:video元素
    }
})();

// ---------------------------------------------------

function getJavaFiles(args) { // 路径/plulic/../..
    console.log(args)
    alert(args)

    // args="/public/upload/files/20180820/a706d74e6e9e4bc8c1d5e52b984047ab.jpg"; //测试用

    let src = Global.host + args;
    let url = args.toLowerCase();

    //是图片
    if (url.indexOf(".jpg") > -1 || url.indexOf(".jpeg") > -1 || url.indexOf(".gif") > -1 || url.indexOf(".png") > -1 || url.indexOf(".bmp") > -1 || url.indexOf(".tga") > -1 || url.indexOf(".svg") > -1) {
        let $liTemp = $(`
            <li class="edit-pic-item">
                <img class="showPic" data-index=${$(".edit-pic-item").length} src="${src}" alt="上传文件">
                <a href="javascript:void(0)" class="edit-closePic"></a>
            </li>
        `);

        //取消图片
        $liTemp.find("a.edit-closePic").click(function (event) {
            event.stopPropagation();
            let self = this;
            Global.messageConfirWin("尚未发布，确认删除？", function () {
                $(self).closest('.edit-pic-item').remove();
            });
        })

        //添加图片成功 后
        $(".showPicUl").prepend($liTemp);
    }
    //是视频
    else if (url.indexOf(".mp4") > -1 || url.indexOf(".rm") > -1 || url.indexOf(".rmvb") > -1 || url.indexOf(".avi") > -1 || url.indexOf(".wmv") > -1 || url.indexOf(".mpg") > -1 || url.indexOf(".mpeg") > -1 || url.indexOf(".flv") > -1 || url.indexOf(".3gp") > -1 || url.indexOf(".mov") > -1) {
        let $liTemp = $(`
            <li class="edit-pic-item">
                <video class="showPic" src="${src}" style="" preload="auto"></video>
                <a href="javascript:void(0)" class="edit-closePic"></a>
            </li>
        `);

        //取消图片
        $liTemp.find("a.edit-closePic").click(function (event) {
            event.stopPropagation();
            let self = this;
            Global.messageConfirWin("尚未发布，确认删除？", function () {
                $(self).closest('.edit-pic-item').remove();
            });
        })

        //添加图片成功 后
        $(".showPicUl").prepend($liTemp);
    }
}