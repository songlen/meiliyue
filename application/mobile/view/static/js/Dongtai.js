$(function () {
    var dongtaiVm = new Vue({
        el: "#dongtaiApp",
        data: {
            GlobalHost: Global.host,

            user_id: "",
            isShowArea: false,
            nowPage: "area", //area attended video
            nowArea: "同城", //同城 全网
            areaDataList: {
                list: [],
                isLoad: false,
                page: 1
            },
            attendedDataList: {
                list: [],
                isLoad: false,
                page: 1
            },
            videoDataList: {
                list: [],
                isLoad: false,
                page: 1
            },

            isReleasing: false,
            isLoading: false,
        },
        computed: {
            nowDataList() {
                return this.nowPage + "DataList"
            }
        },
        mounted: function () {
            let _self = this
            //监听滚动条
            document.getElementsByClassName('pageWrap')[0].addEventListener('scroll', this.handleScroll)
            //下拉刷新
            this.initPullToRefresh();

            //----------------------------------------

            //先查session,把session的值放到data里
            if (sessionStorage.getItem("dongtaiPage") && sessionStorage.getItem("dongtaiPage") !== null) {
                let dongtaiPageData = JSON.parse(sessionStorage.getItem("dongtaiPage"))
                console.log(dongtaiPageData)

                this.user_id = dongtaiPageData.user_id
                this.nowPage = dongtaiPageData.nowPage
                this.nowArea = dongtaiPageData.nowArea
                this.areaDataList = dongtaiPageData.areaDataList
                this.attendedDataList = dongtaiPageData.attendedDataList
                this.videoDataList = dongtaiPageData.videoDataList

                //滚动位置
                setTimeout(function () {
                    document.getElementsByClassName('pageWrap')[0].scrollTop = dongtaiPageData.pageScrollTop
                }, 1)

                return;
            }

            //获取user_id
            if (localStorage.getItem("mUserInfo") && localStorage.getItem("mUserInfo") !== null && localStorage.getItem("mUserInfo") !== "null") {
                let mUserInfo = JSON.parse(JSON.parse(localStorage.getItem("mUserInfo")));
                console.log(mUserInfo)
                this.user_id = Number(mUserInfo.user_id);
            } else {
                this.user_id = 1; //测试用
            }

            //
            this.getListData("areaDataList", false, {
                user_id: this.user_id,
                range: 1
            });

            //获取video的第一帧
            this.getFirstCanvas();
        },
        filters: {
            //时间戳 转 文字
            stampToStr: function (stamp) { //传来的stamp十位数
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
                    let dateStr = Global.stampToDate(stamp);
                    let minStr = dateStr.substr(dateStr.length - 5);
                    return "昨天 " + minStr;
                } else if (howLong > 86400) { //大于24小时，直接显示时间
                    return Global.stampToDate(stamp);
                }
            },
        },
        methods: {
            toHtml(value) {
                value = "" + value;
                return value.replace(/&lt;/g, "<").replace(/&gt;/g, ">").replace(/&quot;/g, "");
            },
            cutTab(pageStr) {
                if (pageStr == "area" && this.nowPage == "area") {
                    // this.isShowArea = true
                    this.isShowArea = !this.isShowArea;
                } else {
                    this.isShowArea = false;
                }
                this.nowPage = pageStr
                //查数据
                if (pageStr !== "area") {
                    if (!this[this.nowDataList].isLoad) { //还没加载过数据
                        this.getListData(this.nowDataList, false, {
                            user_id: this.user_id,
                            attention: this.nowPage == "attended" ? 1 : null,
                            jizha: this.nowPage == "video" ? 1 : null
                        })
                    }
                }
            },
            cutArea(areaStr) { //同城 全网
                this.isShowArea = false
                //查数据
                if (this.nowArea !== areaStr) {
                    this.getListData(this.nowDataList, false, {
                        user_id: this.user_id,
                        range: areaStr == "同城" ? 1 : 2
                    })

                    this.nowArea = areaStr
                }
            },
            //获取数据
            getListData(dataList, isScroll, {
                user_id = null,
                range = null,
                attention = null,
                jizha = null,
                page = null
            } = {}) { //更新的list str ,是否为滚动 boolean,区域str
                // debugger
                let self = this
                console.log("ajax")
                let postData = {
                    user_id: user_id,
                    range: range, //1同城2全网
                    attention: attention, //1关注
                    jizha: jizha, //1叽喳
                    page: page,
                }
                console.log(postData)
                $.ajax({
                    type: "POST",
                    url: self.GlobalHost + "/index.php/Api/dynamics/index",
                    data: postData,
                    dataType: "json",
                    success: function (result) {
                        console.log(result.data)
                        if (isScroll) { //是滚动
                            self[dataList].list = self[dataList].list.concat(result.data)

                            if (result.data.length !== 0) {
                                self[dataList].page = self[dataList].page + 1;
                            }
                        } else {
                            self[dataList].list = result.data

                            //显示 "无任何内容"
                            if (self.nowPage == "attended" && result.data.length == 0) {
                                $(".noComments").show();
                            }
                        }

                        self[dataList].isLoad = true

                        //下拉刷新done
                        $(".pageWrap").pullToRefreshDone();
                        self.isLoading = false;
                    }
                })
            },
            toggleReleasing() {
                this.isReleasing = !this.isReleasing
            },
            handleScroll() {
                let scrollTop = document.getElementsByClassName('pageWrap')[0].scrollTop
                console.log(scrollTop)
                let wrapHeight = document.getElementsByClassName('pageWrap')[0].clientHeight
                let ulHeight = document.getElementsByClassName(this.nowPage + "-page")[0].clientHeight
                // console.log(ulHeight)
                if (scrollTop > 0 && scrollTop + wrapHeight > ulHeight) {
                    if (!this.isLoading) {
                        console.log(this.nowDataList)
                        let postData = {}
                        switch (this.nowDataList) {
                            case "areaDataList":
                                postData = {
                                    user_id: this.user_id,
                                    range: this.nowArea == "同城" ? 1 : 2,
                                    page: this[this.nowDataList].page + 1
                                }
                                break;
                            case "attendedDataList":
                                postData = {
                                    user_id: this.user_id,
                                    attention: 1,
                                    page: this.attendedDataList.page + 1
                                }
                                break;
                            case "videoDataList":
                                postData = {
                                    user_id: this.user_id,
                                    jizha: 1,
                                    page: this.videoDataList.page + 1
                                }
                                break;
                            default:
                                break;
                        }
                        this.isLoading = true;
                        this.getListData(this.nowDataList, true, postData)
                    }
                }
            },
            openEdit(type) { //动态类型
                //window.location("edit.html?user_id=" + 123)
                this.savePageToSession();

                // window.location.href = "edit.html"

                window.location.href = this.GlobalHost + "/index.php/mobile/dynamics/add/type/" + type + ".html";
            },
            //头像加载失败，默认图片
            defaultImg(event) {
                event.target.src = "__STATIC__/images/icon/tx.png"
            },
            //跳转之前存数据到session
            savePageToSession() {
                //key : dongtaiPage
                let pageData = {
                    user_id: this.user_id,
                    nowPage: this.nowPage,
                    nowArea: this.nowArea,
                    areaDataList: this.areaDataList,
                    attendedDataList: this.attendedDataList,
                    videoDataList: this.videoDataList,
                    pageScrollTop: document.getElementsByClassName('pageWrap')[0].scrollTop
                }
                console.log(pageData)

                sessionStorage.setItem('dongtaiPage', JSON.stringify(pageData))
            },
            //打开全屏小视频
            videoFullScreen() {
                var srcTemp = "http://www.w3school.com.cn/example/html5/mov_bbb.mp4"; //测试用,传来的src
                var video = document.getElementById("video1");
                if (!srcTemp == $(video).attr("src")) {
                    $(video).attr("src", srcTemp);
                }

                $(".fullScreen").show()
                video.currentTime = 0; //总是从头开始播放

                //点击 控制video
                $(".fullScreen video").click(function (event) {
                    event.stopPropagation()
                    console.log(this.paused)
                    if (this.paused) {
                        this.play()
                    } else {
                        this.pause()
                    }
                });

                //进度条
                setInterval(function () {
                    var vid = document.getElementById("video1")
                    if (vid.currentTime >= vid.duration) {
                        //触发
                        return false
                    }
                    console.log(vid.currentTime, vid.duration)
                    document.getElementsByClassName("progressBar")[0].style.width = (vid.currentTime / vid.duration) * 100 + "%"
                }, 50);

            },
            //关闭全屏小视频
            closeFullScreen() {
                let video1=document.getElementById("video1");
                $(video1).attr("src","");
                $(".fullScreen").hide();
            },
            gotoDongtaiDetail(item) {
                this.savePageToSession();

                window.location.href = this.GlobalHost + '/index.php/mobile/dynamics/detail/id/' + item.dynamic_id + '.html'
            },
            gotoHomePage(item) {
                this.savePageToSession();

                let user_id = this.user_id;
                let toUserId = item.user_id;
                let url = "";
                if (user_id === toUserId) {
                    url = this.GlobalHost + "/index.php/mobile/user/myHomePage.html";
                } else {
                    url = this.GlobalHost + "/index.php/mobile/user/homePage/user_id/" + user_id + "/toUserId/" + toUserId + ".html";
                }
                window.location.href = url;
            },
            //初始化下拉刷新
            initPullToRefresh() {
                let _self = this;
                //下拉刷新
                $(".pageWrap").pullToRefresh(function () {
                    console.log("刷新")
                    let postData = {}
                    switch (_self.nowDataList) {
                        case "areaDataList":
                            postData = {
                                user_id: _self.user_id,
                                range: _self.nowArea == "同城" ? 1 : 2
                            }
                            break;
                        case "attendedDataList":
                            postData = {
                                user_id: _self.user_id,
                                attention: 1
                            }
                            break;
                        case "videoDataList":
                            postData = {
                                user_id: _self.user_id,
                                jizha: 1
                            }
                            break;
                        default:
                            break;
                    }
                    _self.getListData(_self.nowDataList, false, postData)
                });
            },
            openVideoScreen(item) {
                let self = this;
                let toUserId = item.user_id;
                let head_pic = this.GlobalHost + item.head_pic;
                console.log(head_pic)
                this.getVideoUrl(toUserId, function (url) {
                    // url="https://media.w3.org/2010/05/sintel/trailer.mp4";  //测试用
                    self.showVideo({
                        user_id: toUserId,
                        head_pic: head_pic,
                        src: url
                    });
                });
            },
            getVideoUrl(user_id, callback) { //user_id
                let self=this;
                $.ajax({
                    type: "POST",
                    url: self.GlobalHost + "/index.php/Api/user/getAuthVideoUrl",
                    data: {
                        user_id: user_id
                    },
                    dataType: "json",
                    success: function (result) {
                        console.log(result)
                        if (result.code == 200) { //测试用
                            let url = self.GlobalHost + result.data.video_url;
                            callback(url);
                        }
                    }
                });
            },
            showVideo: function ({
                user_id,
                head_pic,
                src
            }) {
                console.log(user_id, head_pic, src)
                let $fullScreen = $(`
                    <div class="fullScreen">
                        <div class="videoHeader">
                            <div class="closeFullScreen"></div>
                            <div class="videoOperate"></div>
                            <div class="img-box">
                                <img src=${head_pic}>
                            </div>
                        </div>

                        <div class="fullScreenScroll">
                            <div class="fullScreenWrap">
                                <span class="progressBar"></span>
                                <video id="video2" width="100%" height="100%" src=${src} autoplay loop></video>
                                <div class="videoFooter">
                                    <div class="videoCommentBtn"></div>
                                    <span class="littleTri"></span>
                                </div>
                            </div>
                            <!-- 小视频评论 -->
                            <div class="" style="height: 300px;background-color: red;;">

                            </div>
                            
                            <!-- 小视频评论input -->
                            <div class="videoInput">
                                <input type="text">
                                <span class="sendComment">发送</span>
                            </div>
                        </div>
                    </div>
                `);

                //点击关闭事件
                $fullScreen.find(".closeFullScreen").click(function () {
                    $fullScreen.remove();
                });

                $("body").append($fullScreen);
            },
            messageWin: function (msg) {
                Global.messageWin(msg);
            },
            //动态列表送花
            giveFlower: function (item) {
                var _self = this;
                console.log(item)
                //用户id  动态id
                var postData = {
                    user_id: this.user_id,
                    dynamic_id: item.dynamic_id
                }
                console.log(postData)
                // this.messageWin("您的小花数量不足！");//测试用
                // return;
                $.ajax({
                    type: "POST",
                    url: _self.GlobalHost + "/index.php/Api/Dynamics/giveFlower",
                    data: postData,
                    dataType: "json",
                    success: function (result) {
                        console.log(result)
                        if (result.msg == "操作成功" && result.code !== 400) {
                            //小花数量加一
                            item.flower_num = item.flower_num + 1;
                        } else { //送花失败
                            _self.messageWin("您的小花数量不足！");
                        }
                    }
                });
            },
            closeReleasing: function () {
                if (this.isReleasing) {
                    console.log(event)
                    if ($(event.target).closest(".release-start").length == 0) {
                        this.isReleasing = false;
                    }
                }
            },
            //获取video第一帧
            getFirstCanvas(){
                let $videos=$(".shortVideo-page .mVideo video");
                $videos.each(function(){
                    let video=this;
                    console.log(video)
                    video.addEventListener("loadeddata", function () {
                        let canvas = document.createElement("canvas");
                        canvas.width = video.videoWidth * 0.8;
                        canvas.height = video.videoHeight * 0.8;
                        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                        console.log(666)
                        video.setAttribute("poster", canvas.toDataURL("image/png"));
                    })
                });

            }
        }
    });

    // //someEventBind
    // $(".body").click(function () {
    //     console.log("body")
    //     if (dongtaiVm.isReleasing) {
    //         dongtaiVm.isReleasing = false;
    //     }
    // });
});