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
            //msg点击事件
            $msgDiv.find(".msgCtrl").click(function () {
                $msgDiv.remove();
            });
            $("body").append($msgDiv);
        }
    }

    function bindLimitCount(element, textCount, showElment) {
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
            if (showElment) {
                showElment.innerHTML = count
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

    //暴露的接口------------------------
    return {
        //值
        host: "http://meiliyue.caapa.org",
        //方法
        openLoading,
        closeLoading,
        messageWin,
        bindLimitCount, //无表情
        stampToDate,
        stampToStr
    }
})();