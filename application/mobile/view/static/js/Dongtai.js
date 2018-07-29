$(function () {
    var dongtaiVm = new Vue({
        el: "#dongtaiApp",
        data: {
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
        beforeMount() {

            // //先查session,把session的值放到data里
            // if (sessionStorage.getItem("dongtaiPage")) {
            //     let dongtaiPageData = JSON.parse(sessionStorage.getItem("dongtaiPage"))
            //     console.log(dongtaiPageData)

            //     this.user_id = dongtaiPageData.user_id
            //     this.nowPage = dongtaiPageData.nowPage
            //     this.nowArea = dongtaiPageData.nowArea
            //     this.areaDataList = dongtaiPageData.areaDataList
            //     this.attendedDataList = dongtaiPageData.attendedDataList
            //     this.videoDataList = dongtaiPageData.videoDataList

            //     //滚动位置
            //     document.getElementsByClassName('pageWrap')[0].scrollTop = dongtaiPageData.pageScrollTop

            //     return
            // }


            // this.user_id = "1"

            // this.getListData("areaDataList", false, {
            //     user_id: this.user_id,
            //     range: 1
            // })
        },
        mounted: function () {

            document.getElementsByClassName('pageWrap')[0].addEventListener('scroll', this.handleScroll)

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

                return
            }


            this.user_id = "1"

            this.getListData("areaDataList", false, {
                user_id: this.user_id,
                range: 1
            })



        },
        methods: {
            cutTab(pageStr) {
                if (pageStr == "area" && this.nowPage == "area") {
                    this.isShowArea = true
                }
                this.nowPage = pageStr
                //查数据
                if (pageStr !== "area") {
                    if (!this[this.nowDataList].isLoad) { //还没加载过数据
                        this.getListData(this.nowDataList, false, {
                            user_id: this.user_id,
                            attention: this.nowPage == "attended" ? 1 : "",
                            jizha: this.nowPage == "video" ? 1 : ""
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
            getListData(dataList, isScroll, postData) { //更新的list str ,是否为滚动 boolean,区域str
                // debugger
                let self = this
                console.log("ajax")
                // let postData={
                //     user_id:"12",
                //     range:"1",//1同城2全网
                //     attention:"1",//1关注
                //     jizha:"1"//1叽喳
                // }
                console.log(postData)
                $.ajax({
                    type: "POST",
                    url: "http://meiliyue.caapa.org/index.php/Api/dynamics/index",
                    data: postData,
                    dataType: "json",
                    success: function (result) {
                        console.log(result.data)
                        if (isScroll) { //是滚动
                            self[dataList].list = self[dataList].list.concat(result.data)
                        } else {
                            self[dataList].list = result.data
                        }

                        self[dataList].isLoad = true
                        if (isScroll && result.data.length !== 0) {
                            self[dataList].page = self[dataList].page + 1
                        }
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
            openEdit() {
                //window.location("edit.html?user_id=" + 123)
                this.savePageToSession()

                window.location.href = "edit.html"
                //  /mobile/dynamics/add 这里地址怎么写？
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
            }
        }
    });


});