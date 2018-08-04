$(function () {
    var GlobalHost = "http://meiliyue.caapa.org";
    var dongtaiVm = new Vue({
        el: "#dongtaiApp",
        data: {
            GlobalHost:GlobalHost,

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
            if (sessionStorage.getItem("dongtaiPage")) {
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

            this.user_id = 1;

            this.getListData("areaDataList", false, {
                user_id: this.user_id,
                range: 1
            })

        },
        filters: {
            //时间戳 转 文字
            stampToStr: function (stamp) {//传来的stamp十位数
                if (!stamp) return '';

                let nowStamp=Math.round((new Date().getTime())/1000);
                let howLong=nowStamp-stamp;

                if(howLong<60){//1分钟内
                    return "1分钟内";
                }else if(howLong<3600){//1小时内，显示分钟数
                    let minutes=Math.floor(howLong/60);
                    return minutes+"分钟前";
                }else if(howLong<43200){//12小时内，显示小时数
                    let hours=Math.floor(howLong/3600);
                    return hours+"小时前";
                }else if(howLong>=43200&&howLong<86400){//大于12小时，小于24小时
                    let dateStr=stampToDate(stamp);
                    console.log(dateStr)
                    let minStr=dateStr.substr(dateStr.length-5);
                    return "昨天 "+minStr;
                }else if(howLong>86400){//大于24小时，直接显示时间
                    return stampToDate(stamp);
                }
            }
        },
        methods: {
            cutTab(pageStr) {
                if (pageStr == "area" && this.nowPage == "area") {
                    // this.isShowArea = true
                    this.isShowArea = !this.isShowArea;
                }else{
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
                    jizha: jizha //1叽喳
                }
                console.log(postData)
                $.ajax({
                    type: "POST",
                    url: GlobalHost+"/index.php/Api/dynamics/index",
                    data: postData,
                    dataType: "json",
                    success: function (result) {
                        console.log(result.data)
                        if (isScroll) { //是滚动
                            self[dataList].list = self[dataList].list.concat(result.data)

                            if(result.data.length !== 0){
                                self[dataList].page = self[dataList].page + 1;
                            }
                        } else {
                            self[dataList].list = result.data

                            //显示 "无任何内容"
                            if(self.nowPage=="attended"&&result.data.length==0){
                                $(".noComments").show();
                            }
                        }

                        self[dataList].isLoad = true

                        //下拉刷新done
                        $(".pageWrap").pullToRefreshDone();
                    }
                })
            },
            toggleReleasing() {
                this.isReleasing = !this.isReleasing
            },
            sendFlower() {
                console.log("送花")
            },
            handleScroll() {
                let scrollTop = document.getElementsByClassName('pageWrap')[0].scrollTop
                console.log(scrollTop)
                let wrapHeight = document.getElementsByClassName('pageWrap')[0].clientHeight
                let ulHeight = document.getElementsByClassName(this.nowPage + "-page")[0].clientHeight
                // console.log(ulHeight)
                if (scrollTop > 0 && scrollTop + wrapHeight > ulHeight) {
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
                    this.getListData(this.nowDataList, true, postData)
                }
            },
            openEdit(type) { //动态类型
                //window.location("edit.html?user_id=" + 123)
                this.savePageToSession();

                // window.location.href = "edit.html"

                window.location.href = GlobalHost + "/index.php/mobile/dynamics/add/type/" + type+".html";
            },
            //头像加载失败，默认图片
            defaultImg(event) {
                event.target.src = "../images/icon/tx.png"
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
                var srcTemp = "https://media.w3.org/2010/05/sintel/trailer.mp4";//测试用,传来的src
                var video=document.getElementById("video1");
                if(!srcTemp==$(video).attr("src")){
                    $(video).attr("src",srcTemp);
                }

                $(".fullScreen").show()
                video.currentTime = 0;//总是从头开始播放
                
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
                $(".fullScreen").hide()
            },
            gotoDongtaiDetail(item){
                this.savePageToSession();

                window.location.href=GlobalHost+'/index.php/mobile/dynamics/detail/id/'+item.dynamic_id+'.html'
            },
            gotoHomePage(item){
                this.savePageToSession();

                let user_id=this.user_id;
                let toUserId=item.user_id;
                window.location.href = GlobalHost + "/index.php/mobile/user/homePage/user_id/"+user_id+"/toUserId/"+toUserId+".html";
            },
            //初始化下拉刷新
            initPullToRefresh(){
                let _self=this;
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
            }
        }
    });
});
//时间戳转日期时间
function stampToDate(timestamp) {
    var date = new Date(timestamp * 1000);//时间戳为10位需*1000，时间戳为13位的话不需乘1000
    Y = date.getFullYear() + '-';
    M = (date.getMonth()+1 < 10 ? '0'+(date.getMonth()+1) : date.getMonth()+1) + '-';
    D = (date.getDate()<10?"0"+date.getDate():date.getDate()) + ' ';
    h = (date.getHours()<10?"0"+date.getHours():date.getHours()) + ':';
    m = (date.getMinutes()<10?"0"+date.getMinutes():date.getMinutes());
    // s = date.getSeconds();
    return Y+M+D+h+m;
}