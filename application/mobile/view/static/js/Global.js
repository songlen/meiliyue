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

    function gotoApp(funcStr) {
        let u = navigator.userAgent;
        let isAndroid = u.indexOf('Android') > -1 || u.indexOf('Adr') > -1; //android终端
        let isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
        console.log(isAndroid, isiOS)
        if (isAndroid) {
            window.Android[funcStr]();
        } else if (isiOS) {
            //
            console.log(3)
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
        messageConfirWin,
        bindLimitCount, //无表情
        stampToDate,
        stampToStr,
        gotoApp
    }
})();

function getJavaFiles(args) {
    console.log(args)
    alert("修改过"+args)

    // args='C:/Users/xq/Desktop/微信图片_20180820114149.jpg'; //测试用

    fetchAB(args, function (abf) {
        let url = args.toLowerCase();
        if (url.indexOf(".jpg") > -1 || url.indexOf(".jpeg") > -1 || url.indexOf(".gif") > -1 || url.indexOf(".png") > -1 || url.indexOf(".bmp") > -1 || url.indexOf(".tga") > -1 || url.indexOf(".svg") > -1) { //是图片
            let blob=new Blob([abf],{type:"image/jpeg"});
            console.log(blob)
            console.log(blob.size)
            console.log(blob.type)
            alert(blob)
            alert(blob.size)
            alert(blob.type)

            alert(`
                <li class="edit-pic-item">
                    <img class="showPic" data-index=${$(".edit-pic-item").length} src=${'"'+args+'"'} alt="上传文件">
                </li>
            `)

            let $li = $(`
                <li class="edit-pic-item">
                    <img class="showPic" data-index=${$(".edit-pic-item").length} src=${'"'+args+'"'} alt="上传文件">
                </li>
            `);

            //添加图片成功 后
            $(".showPicUl").prepend($li);

            // let reader = new FileReader();
            // reader.onload = function (e) {
            //     let $li = $(`
            //         <li class="edit-pic-item">
            //             <img class="showPic" data-index=${$(".edit-pic-item").length} src="../static/images/icon/tx.png" alt="上传文件">
            //         </li>
            //     `);
            //     console.log(e.target.result,e.target.result.length)
            //     alert(e.target.result)
            //     alert(e.target.result.length)
            //     $li.find(".showPic").attr('src', e.target.result);

            //     //添加图片成功 后
            //     $(".showPicUl").prepend($li);

            //     // //取消图片
            //     // $liTemp.find("a.edit-closePic").click(function () {
            //     //     Edit.cancelPic(this)
            //     // })
            // }
            // reader.readAsDataURL(blob);
        } else if (url.indexOf(".rm") > -1 || url.indexOf(".rmvb") > -1 || url.indexOf(".avi") > -1 || url.indexOf(".wmv") > -1 || url.indexOf(".mpg") > -1 || url.indexOf(".mpeg") > -1 || url.indexOf(".flv") > -1 || url.indexOf(".3gp") > -1) { //是视频
            let blob=new Blob([abf],{type:"video/mp4"});
            console.log(blob)
            console.log(blob.size)
            console.log(blob.type)
            alert(blob)
            alert(blob.size)
            alert(blob.type)
            let reader = new FileReader();
            reader.onload = function (e) {
                // $liTemp.find('.showPic').attr('src', e.target.result);

                alert(e.target.result)

                // //添加图片成功 后
                // $(".showPicUl").prepend($liTemp)

                // //取消图片
                // $liTemp.find("a.edit-closePic").click(function () {
                //     Edit.cancelPic(this)
                // })

            }
            reader.readAsDataURL(blob);
        }
    });
}

//url => blob
function fetchAB(url, cb) {
    console.log(url);
    var xhr = new XMLHttpRequest;
    xhr.open('get', url);
    xhr.responseType = 'arraybuffer';
    xhr.onload = function () {
        cb(xhr.response); //arraybuffer
    };
    xhr.send();
};

// function readBlob(blob) {
//     var reader = new FileReader();
//     reader.onload = function (e) {
//         $liTemp.find('.showPic').attr('src', e.target.result);

//         //添加图片成功 后
//         $(".showPicUl").prepend($liTemp)

//         //取消图片
//         $liTemp.find("a.edit-closePic").click(function () {
//             Edit.cancelPic(this)
//         })

//     }
//     reader.readAsDataURL(blob);
// }
