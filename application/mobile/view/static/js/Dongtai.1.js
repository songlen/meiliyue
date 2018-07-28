$(function () {
    var dongtaiVm = new Vue({
        el: "#dongtaiApp",
        data: {
            isShowArea: false,
            nowPage: "area", //area attended video
            nowArea: "同城", //同城 全网
            areaDataList: {
                list: [],
                isLoad: false
            },
            attendedDataList: {
                list: [],
                isLoad: false
            },
            videoDataList: {
                list: [],
                isLoad: false
            },
            isReleasing: false,
        },
        computed: {
            nowDataList() {
                return this.nowPage + "DataList"
            }
        },
        beforeMount() {
            this.getListData("areaDataList", false, "同城")
        },
        mounted: function () {
            document.getElementsByClassName('pageWrap')[0].addEventListener('scroll', this.handleScroll)
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
                        this.getListData(this.nowDataList, false)
                    }
                }
            },
            cutArea(areaStr) { //同城 全网
                this.isShowArea = false
                //查数据
                if (this.nowArea !== areaStr) {
                    this.getListData(this.nowDataList, false, areaStr)

                    this.nowArea = areaStr
                }
            },
            //获取数据
            {x = 0, y = 0} = {}
            getListData(dataList, isScroll, area) { //更新的list str ,是否为滚动 boolean,区域str
                // debugger
                let self = this
                console.log("ajax")
                let postData={
                    user_id:"12",
                    range:"1",//1同城2全网
                    attention:"1",//1关注
                    jizha:"1"//1叽喳
                }
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
                let wrapHeight = document.getElementsByClassName('pageWrap')[0].clientHeight
                let ulHeight = document.getElementsByClassName(this.nowPage + "-page")[0].clientHeight
                console.log(ulHeight)
                if (scrollTop > 0 && scrollTop + wrapHeight > ulHeight) {
                    this.getListData(this.nowDataList, true)
                }
            },
            openEdit() {
                window.open("edit.html?user_id=" + 123)
            }
        }
    });


});